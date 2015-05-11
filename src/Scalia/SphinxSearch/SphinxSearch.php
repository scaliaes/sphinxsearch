<?php namespace Scalia\SphinxSearch;

class SphinxSearch {
  protected $_connection;
  protected $_index_name;
  protected $_search_string;
  protected $_config;
  protected $_total_count;
  protected $_time;
  protected $_eager_loads;

  public function __construct()
  {
    $host = \Config::get('sphinxsearch.host');
    $port = \Config::get('sphinxsearch.port');
    $this->_connection = new \Sphinx\SphinxClient();
    $this->_connection->setServer($host, $port);
    $this->_connection->setMatchMode(\Sphinx\SphinxClient::SPH_MATCH_ANY);
    $this->_connection->setSortMode(\Sphinx\SphinxClient::SPH_SORT_RELEVANCE);
    $this->_config = \Config::get('sphinxsearch.indexes');
    reset($this->_config);
    $this->_index_name = isset($this->_config['name'])?implode(',', $this->_config['name']):key($this->_config);
    $this->_eager_loads = array();
  }

  public function search($string, $index_name = null)
  {
    $this->_search_string = $string;
    if (null !== $index_name)
    {
      // if index name contains , or ' ', multiple index search
      if (strpos($index_name, ' ') || strpos($index_name,','))
      {
        if (!isset($this->_config['mapping']))
        {
          $this->_config['mapping']=false;
        }
      }
      $this->_index_name = $index_name;
    }

    $this->_connection->resetFilters();

    return $this;
  }

  public function setFieldWeights($weights)
  {
    $this->_connection->setFieldWeights($weights);
    return $this;
  }

  public function setMatchMode($mode)
  {
    $this->_connection->setMatchMode($mode);
    return $this;
  }

  public function setRankingMode($mode)
  {
    $this->_connection->setRankingMode($mode);
    return $this;
  }

  public function setSortMode($mode, $sortby = null)
  {
    $this->_connection->setSortMode($mode, $sortby);
    return $this;
  }

  public function setFilterFloatRange($attribute, $min, $max, $exclude = false)
  {
    $this->_connection->setFilterFloatRange($attribute, $min, $max, $exclude);
    return $this;
  }

  public function setGeoAnchor($attrlat, $attrlong, $lat = null, $long = null)
  {
    $this->_connection->setGeoAnchor($attrlat, $attrlong, $lat, $long);
    return $this;
  }

  public function setSelect($select)
  {
    $this->_connection->setSelect($select);
    return $this;
  }

  public function limit($limit, $offset = 0, $max_matches = 1000, $cutoff = 1000)
  {
    $this->_connection->setLimits($offset, $limit, $max_matches, $cutoff);
    return $this;
  }

  public function filter($attribute, $values, $exclude = false)
  {
    if (is_array($values))
    {
      $val = array();
      foreach($values as $v)
      {
        $val[] = (int) $v;
      }
    }
    else
    {
      $val = array((int) $values);
    }
    $this->_connection->setFilter($attribute, $val, $exclude);

    return $this;
  }

  public function range($attribute, $min, $max, $exclude = FALSE)
  {
    $this->_connection->setFilterRange($attribute, $min, $max, $exclude);
    return $this;
  }

  public function query()
  {
    return $this->_connection->query($this->_search_string, $this->_index_name);
  }

  public function get($respect_sort_order = false)
  {
    $this->_total_count = 0;
    $result             = $this->_connection->query($this->_search_string, $this->_index_name);

    // Process results.
    if ($result)
    {
      // Get total count of existing results.
      $this->_total_count = (int) $result['total_found'];
      // Get time taken for search.
      $this->_time = $result['time'];

      if($result['total'] && isset($result['matches']))
      {
        // Get results' id's and query the database.
        $matchids = array_keys($result['matches']);

        $config = isset($this->_config['mapping']) ? $this->_config['mapping'] : $this->_config[$this->_index_name];
        if ($config)
        {
          if (isset($config['modelname']))
          {
            if ($this->_eager_loads) {
              $result = call_user_func_array($config['modelname'] . "::whereIn", array($config['column'], $matchids))->with($this->_eager_loads)->get();
            } else {
              $result = call_user_func_array($config['modelname'] . "::whereIn", array($config['column'], $matchids))->get();
            }
          }
          else
          {
            $result = \DB::table($config['table'])->whereIn($config['column'], $matchids)->get();
          }

        }
      }
      else
      {
        $result = array();
      }
    }

    if($respect_sort_order)
    {
      if(isset($matchids))
      {
        $return_val = array();
        foreach($matchids as $matchid)
        {
          $key = self::getResultKeyByID($matchid, $result);
          if (false !== $key)
          {
            $return_val[] = $result[$key];
          }
        }
        return $return_val;
      }
    }

    // important: reset the array of eager loads prior to making next call
    $this->_eager_loads = array();

    return $result;
  }

  public function with()
  {
    $this->_eager_loads = array();

    foreach (func_get_args() as $a)
    {
      $this->_eager_loads[] = $a;
    }

    return $this;
  }

  public function getTotalCount()
  {
    return $this->_total_count;
  }

  public function getTime()
  {
    return $this->_time;
  }

  public function getErrorMessage()
  {
    return $this->_connection->getLastError();
  }

  private function getResultKeyByID($id, $result)
  {
    if(count($result) > 0)
    {
      foreach($result as $k => $result_item)
      {

        if ( $result_item->id == $id )
        {
          return $k;
        }
      }
    }
    return false;
  }
}
