<?php namespace Scalia\SphinxSearch;

class SphinxSearch {
  private $_connection;
  private $_index_name;
  private $_search_string;
  private $_config;
  private $_total_count;

  function __construct()
  {
    $host = \Config::get('sphinxsearch::host');
    $port = \Config::get('sphinxsearch::port');
    $this->_connection = new \Sphinx\SphinxClient();
    $this->_connection->setServer($host, $port);
    $this->_config = \Config::get('sphinxsearch::indexes');
    reset($this->_config);
    $this->_index_name = key($this->_config);
  }

  function search($string, $index_name = NULL)
  {
    $this->_search_string = $string;
    if (NULL !== $index_name)
    {
      $this->_index_name = $index_name;
    }

    $this->_connection->setMatchMode(\Sphinx\SphinxClient::SPH_MATCH_ANY);
    $this->_connection->setSortMode(\Sphinx\SphinxClient::SPH_SORT_RELEVANCE);
    $this->_connection->resetFilters();

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

  function get()
  {
    $this->_total_count = 0;
    $result             = $this->_connection->query($this->_search_string, $this->_index_name);

    // Process results.
    if ($result)
    {
      // Get total count of existing results.
      $this->_total_count = (int) $result['total_found'];

      if($result['total'] > 0 && isset($result['matches']))
      {
        // Get results' id's and query the database.
        $result = array_keys($result['matches']);

        $config = $this->_config[$this->_index_name];
        if ($config)
        {
          $result = \DB::table($config['table'])->whereIn($config['column'], $result)->get();
        }
      }
      else
      {
        $result = array();
      }
    }

    return $result;
  }

  function getTotalCount()
  {
    return $this->_total_count;
  }

  function getErrorMessage()
  {
    return $this->_connection->getLastError();
  }
}
