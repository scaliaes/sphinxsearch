<?php
/**
 * Created by PhpStorm.
 * User: ianloverink
 * Date: 12/18/13
 */
use \Mockery as m;
use Scalia\SphinxSearch\SphinxSearch;
class SphinxSearchTest extends Orchestra\Testbench\TestCase
{
    protected function getPackageProviders()
    {
        return array('Scalia\SphinxSearch\SphinxSearchServiceProvider');
    }

    protected function makeSearch($mock)
    {
        return new SphinxSearch($mock);
    }

    protected function makeMock()
    {
        return m::mock('Sphinx\SphinxClient',array());
    }

    protected function mockConfig($models = false)
    {
        \Config::shouldReceive('get')
            ->withArgs(array('sphinxsearch::default_search'))
            ->andReturn('default');

        $default = array('indexes'=>array('main','delta'));
        if(!$models)
        {
            $default['mapping'] = array('table'=>'test','column'=>'id');
        }
        else
        {
            $default['mapping'] = array('model'=>'Test','column'=>'id');
        }
        \Config::shouldReceive('get')
            ->withArgs(array('sphinxsearch::searches'))
            ->andReturn(array('default'=>$default));
    }

    protected function mockResults()
    {
        return array(
            'total_found' => 10,
            'total' => 1,
            'time' => '1',
            'matches'=> array(
                1 => 'found'
            )
        );
    }

    public function testSetFieldWeights()
    {
        $mock = $this->makeMock();
        $mock->shouldReceive('setFieldWeights')->once();
        $search = $this->makeSearch($mock);
        $search->setFieldWeights(array('test'=>1));
    }

    public function testSetMatchMode()
    {
        $mock = $this->makeMock();
        $mock->shouldReceive('setMatchMode')->once();
        $search = $this->makeSearch($mock);
        $search->setMatchMode(1);
    }

    public function testSetRankingMode()
    {
        $mock = $this->makeMock();
        $mock->shouldReceive('setRankingMode')->once();
        $search = $this->makeSearch($mock);
        $search->setRankingMode(1);
    }


    public function testSetSortMode()
    {
        $mock = $this->makeMock();
        $mock->shouldReceive('setSortMode')->once();
        $search = $this->makeSearch($mock);
        $search->setSortMode(1,'');
    }

    public function testLimit()
    {
        $mock = $this->makeMock();
        $mock->shouldReceive('setLimits')->once();
        $search = $this->makeSearch($mock);
        $search->limit(1);
    }

    public function testFilterArray()
    {
        $mock = $this->makeMock();
        $mock->shouldReceive('setFilter')
            ->withArgs(array('test',array(1,2,10),false))
            ->once();
        $search = $this->makeSearch($mock);
        $search->filter('test',array(1,'2',10));
    }

    public function testFilterInt()
    {
        $mock = $this->makeMock();
        $mock->shouldReceive('setFilter')
            ->withArgs(array('test',array(1),false))
            ->once();
        $search = $this->makeSearch($mock);
        $search->filter('test',1);
    }

    public function testRange()
    {
        $mock = $this->makeMock();
        $mock->shouldReceive('setFilterRange')
            ->withArgs(array('test',1,10,false))
            ->once();
        $search = $this->makeSearch($mock);
        $search->range('test',1,10);
    }

    public function testGetErrorMessage()
    {
        $mock = $this->makeMock();
        $mock->shouldReceive('getLastError')->once()->andReturn('test error');
        $search = $this->makeSearch($mock);
        $msg = $search->getErrorMessage();
        $this->assertEquals('test error',$msg);
    }

    public function testSearch()
    {
        \Config::shouldReceive('get')
            ->withArgs(array('sphinxsearch::default_search'))
            ->andReturn('default');
        \Config::shouldReceive('get')
            ->withArgs(array('sphinxsearch::searches'))
            ->andReturn(array('default'=>array('indexes'=>array('test'))));

        $mock = $this->makeMock();
        $mock->shouldReceive('resetFilters')->once();
        $search = $this->makeSearch($mock);

        $search->search('test');
    }

    public function testSearchWithMapping()
    {
        $this->mockConfig();
        $mock = $this->makeMock();
        $mock->shouldReceive('resetFilters')->once();
        $search = $this->makeSearch($mock);
        $search->search('test');
    }

    public function testSearchNamedSearch()
    {
        $this->mockConfig();
        $mock = $this->makeMock();
        $mock->shouldReceive('resetFilters')->once();
        $search = $this->makeSearch($mock);

        $search->search('test','default');
    }

    public function testSearchUnknownSearch()
    {
        $this->mockConfig();
        $search = $this->makeSearch($this->makeMock());
        try
        {
            $search->search('test','blah');
        }
        catch(\Exception $e)
        {
            $this->assertTrue(true);
            return;
        }
        $this->fail('exception expected');
    }

    public function testGet()
    {
        $mock = $this->makeMock();

        $mock->shouldReceive('query')->andReturn($this->mockResults());
        $search = $this->makeSearch($mock);

        $search->pretend()->get();
    }

    public function testGetTable()
    {
        $this->mockConfig();
        $mock = $this->makeMock();

        $mock->shouldIgnoreMissing();
        $mock->shouldReceive('query')->andReturn($this->mockResults());
        $search = $this->makeSearch($mock);

        $search->search('default')->pretend()->get();
    }

    public function testGetModel()
    {
        $this->mockConfig(true);
        $mock = $this->makeMock();

        $mock->shouldIgnoreMissing();
        $mock->shouldReceive('query')->andReturn($this->mockResults());
        $search = $this->makeSearch($mock);

        $search->search('default')->pretend()->get();
    }

    public function testGetModelSort()
    {
        $this->mockConfig(true);
        $mock = $this->makeMock();

        $mock->shouldIgnoreMissing();
        $mock->shouldReceive('query')->andReturn($this->mockResults());
        $search = $this->makeSearch($mock);

        $search->search('default')->pretend()->get(true);
    }
}