<?php namespace Scalia\SphinxSearch;

use MyProject\Proxies\__CG__\OtherProject\Proxies\__CG__\stdClass;
use Sphinx\SphinxClient;


class SphinxSearch
{
    /**
     * @var \Sphinx\SphinxClient
     */
    protected $_connection;

    /**
     * @var
     */
    protected $_default_search;

    /**
     * @var
     */
    protected $_indexes;

    /**
     * @var
     */
    protected $_mapping;

    /**
     * @var
     */
    protected $_search_string;

    /**
     * @var
     */
    protected $_config;

    /**
     * @var
     */
    protected $_total_count;

    /**
     * @var
     */
    protected $_time;

    /**
     * @var
     */
    protected $_pretend = false;

    /**
     * @param SphinxClient $connection
     */
    public function __construct(SphinxClient $connection)
    {
        $this->_connection = $connection;
        $this->_default_search = \Config::get('sphinxsearch::default_search');
        $this->_config = \Config::get('sphinxsearch::searches');
    }

    /**
     * @param string $string
     * @param string|null $search_name
     * @return $this
     * @throws \Exception
     */
    public function search($string, $search_name = NULL)
    {
        $this->_search_string = $string;
        if (!empty($search_name))
        {
            if(empty($this->_config[$search_name]))
            {
                throw new \Exception('Search name not found in the config.');
            }
        }
        else
        {
            $search_name = $this->_default_search;
        }
        $this->_indexes = implode(',',$this->_config[$search_name]['indexes']);
        if(!empty($this->_config[$search_name]['mapping']))
        {
            $this->_mapping = $this->_config[$search_name]['mapping'];
        }
        else
        {
            $this->_mapping = false;
        }
        $this->_connection->resetFilters();
        return $this;
    }

    /**
     * @param array $weights
     * @return $this
     */
    public function setFieldWeights(array $weights)
    {
        $this->_connection->setFieldWeights($weights);
        return $this;
    }

    /**
     * @param integer $mode
     * @return $this
     */
    public function setMatchMode($mode)
    {
        $this->_connection->setMatchMode($mode);
        return $this;
    }

    /**
     * @param  integer $mode
     * @return $this
     */
    public function setRankingMode($mode)
    {
        $this->_connection->setRankingMode($mode);
        return $this;
    }

    /**
     * @param integer $mode
     * @param string $par
     * @return $this
     */
    public function setSortMode($mode, $par = '')
    {
        $this->_connection->setSortMode($mode, $par);
        return $this;
    }

    /**
     * @param integer $limit
     * @param integer $offset
     * @param integer $max_matches
     * @param integer $cutoff
     * @return $this
     */
    public function limit($limit, $offset = 0, $max_matches = 1000, $cutoff = 1000)
    {
        $this->_connection->setLimits($offset, $limit, $max_matches, $cutoff);
        return $this;
    }

    /**
     * @param $attribute
     * @param $values
     * @param bool $exclude
     * @return $this
     */
    public function filter($attribute, $values, $exclude = FALSE)
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

    /**
     * @param $attribute
     * @param $min
     * @param $max
     * @param bool $exclude
     * @return $this
     */
    public function range($attribute, $min, $max, $exclude = FALSE)
    {
        $this->_connection->setFilterRange($attribute, $min, $max, $exclude);
        return $this;
    }

    /**
     * @param bool $respect_sort_order
     * @return array|false
     */
    public function get($respect_sort_order = FALSE)
    {
        $this->_total_count = 0;
        $result = $this->_connection->query($this->_search_string, $this->_indexes);

        if($result)
        {
            $this->_total_count = (int)$result['total_found'];
            // Get time taken for search.
            $this->_time = $result['time'];

            if($result['total'] > 0 && isset($result['matches']))
            {
                // Get results' id's and query the database.
                $matchids = array_keys($result['matches']);

                //integrate with eloquent or run a fluent query
                if ($this->_mapping)
                {
                    if(isset($this->_mapping['model']))
                    {
                        $res = $this->queryDatabase('model',$matchids);
                    }
                    else
                    {
                        $res = $this->queryDatabase('table',$matchids);
                    }

                }
                //otherwise just return the ids
                else
                {
                    $res = $matchids;
                    $respect_sort_order = false;
                }
            }
        }

        if($respect_sort_order)
        {
            if(isset($matchids))
            {
                $return_val = array();
                foreach($matchids as $matchid)
                {
                    $key = self::getResultKeyByID($matchid, $res);
                    $return_val[] = $res[$key];
                }
                return $return_val;
            }
        }
        return $res;
    }

    /**
     * @return mixed
     */
    public function getTotalCount()
    {
        return $this->_total_count;
    }

    /**
     * @return mixed
     */
    public function getTime()
    {
        return $this->_time;
    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->_connection->getLastError();
    }

    /**
     * @return $this
     */
    public function pretend()
    {
        $this->_pretend = true;
        return $this;
    }

    /**
     * @param $id
     * @param $result
     * @return bool|int|string
     */
    private function getResultKeyByID($id, $result)
    {
        if(count($result) > 0)
        {
            foreach($result as $k => $result_item)
            {
                $value = is_object($result_item) ? $result_item->id : $result_item['id'];
                if ( $value == $id )
                {
                    return $k;
                }
            }
        }
        return false;
    }

    /**
     * @param $type
     * @param array $matchids
     * @return array
     */
    private function queryDatabase($type,array $matchids)
    {
        $data = array();
        if($this->_pretend)
        {
            $object = new \stdClass();
            $object->id = 1;
            $data = array($object);
        }
        else
        {
            if($type == "model")
            {
                $data = call_user_func_array($this->_mapping['model'] . "::whereIn", array($this->_mapping['column'], $matchids))->get();
            }
            elseif($type == "table")
            {
                $data = \DB::table($this->_mapping['table'])->whereIn($this->_mapping['column'], $matchids)->get();
            }
        }
        return $data;
    }
}
