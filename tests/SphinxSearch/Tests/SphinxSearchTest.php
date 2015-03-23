<?php

namespace 
{ 
  class Config {
    public function get($key)
    {
      $config = array(
        'sphinxsearch::host'    => '127.0.0.1',
        'sphinxsearch::port'    => 9312,
        'sphinxsearch::indexes' => array (
          'my_index_name' => array(
            'table' => 'keywords',
            'column' => 'id'
          ),
        )
      );

      return isset($config[$key]) ? $config[$key] : null;
    }
  }
}

namespace Scalia\SphinxSearch\Tests
{

    use Scalia\SphinxSearch\SphinxSearch;
    use Mockery as m;
          #use \Sphinx\SphinxClient;
    /**
     * SphinxSearch test cases.
     *
     * @author Scalia <contacto@scalia.es>
     */
    class SphinxSearchTest extends \PHPUnit_Framework_TestCase
    {
      protected $sphinxClientMock;

      public function setUp()
      {
        parent::setUp();

        $this->sphinxClientMock = m::mock('\Sphinx\SphinxClient');
        $this->sphinxClientMock->shouldReceive('setServer')->once();
        $this->sphinxClientMock->shouldReceive('setConnectTimeout')->once();
        $this->sphinxClientMock->shouldReceive('setMatchMode')->once();
        $this->sphinxClientMock->shouldReceive('setSortMode')->once();
        $this->sphinxClientMock->shouldReceive('resetFilters')->once();
        $this->sphinxClientMock->shouldReceive('resetGroupBy')->once();
        
        
      }

      public function tearDown()
      {
        m::close();
      }

      public function testConstructorDefaults()
      {
        $queryString = 'the query';
        $sphinxSearch = new SphinxSearch($this->sphinxClientMock);
        
        $this->sphinxClientMock->shouldReceive('query')->once();
        $sphinxSearch->search($queryString)->query();
        
      }
    }

}