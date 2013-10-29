<?php namespace Scalia\SphinxSearch;

class SphinxSearch {
  private $_connection;
  private $_index_name;
  private $_search_string;
  private $_config;
  private $_total_count;
  private $_time;

  function __construct()
  {
    $host = \Config::get('sphinxsearch::host');
    $port = \Config::get('sphinxsearch::port');
    $this->_connection = new \Sphinx\SphinxClient();
    $this->_connection->setServer($host, $port);
    $this->_connection->setMatchMode(\Sphinx\SphinxClient::SPH_MATCH_ANY);
    $this->_connection->setSortMode(\Sphinx\SphinxClient::SPH_SORT_RELEVANCE);
    $this->_config = \Config::get('sphinxsearch::indexes');
    reset($this->_config);
    $this->_index_name = isset($this->_config['name'])?implode(',', $this->_config['name']):key($this->_config);
  }

  function search($string, $index_name = NULL)
  {
    $this->_search_string = $string;
    if (NULL !== $index_name)
    {
       //if index name contains , or ' ', multiple index search
      if (strpos($index_name, ' ')||strpos($index_name,','))
      {
          if (!isset($this->_config['mapping']))
          {
              throw new \Exception('For multiple index search, keys 
                  "name" and "mapping" are required in config file');
          } 
      }
      $this->_index_name = $index_name;
    }

    $this->_connection->resetFilters();

    return $this;
  }

  function setFieldWeights($weights)
  {
    $this->_connection->setFieldWeights($weights);
    return $this;
  }

  function setMatchMode($mode)
  {
    $this->_connection->setMatchMode($mode);
    return $this;
  }

  function setRankingMode($mode)
  {
    $this->_connection->setRankingMode($mode);
    return $this;
  }

  function setSortMode($mode, $par = NULL)
  {
    $this->_connection->setSortMode($mode, $par);
    return $this;
  }

  function limit($limit, $offset = 0, $max_matches = 1000, $cutoff = 1000)
  {
    $this->_connection->setLimits($offset, $limit, $max_matches, $cutoff);
    return $this;
  }

  function filter($attribute, $values, $exclude = FALSE)
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

  function range($attribute, $min, $max, $exclude = FALSE)
  {
    $this->_connection->setFilterRange($attribute, $min, $max, $exclude);
    return $this;
  }

  function get($respect_sort_order = FALSE)
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

      if($result['total'] > 0 && isset($result['matches']))
      {
        // Get results' id's and query the database.
        $matchids = array_keys($result['matches']);

        $config = isset($this->_config['mapping'])?$this->_config['mapping']:$this->_config[$this->_index_name];
	if ($config)
        {
          if(isset($config['modelname']))
          {
            $result = call_user_func_array($config['modelname'] . "::whereIn", array($config['column'], $matchids))->get();  
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
          $return_val[] = $result[$key];
        }
        return $return_val;  
      }
    }

    return $result;    
  }

  function getTotalCount()
  {
    return $this->_total_count;
  }
  function getTime()
  {
    return $this->_time;
  }

  function getErrorMessage()
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
