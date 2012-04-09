<?php

    require_once dirname(__FILE__)."/../../lib/Sample.php";

    class SampleTest extends PHPUnit_Framework_TestCase
    {
        /**
         * @var Sample
         */
        private $sample;

        protected function setUp()
        {
            $this->sample = new Sample();
            $this->sample->setName("Danny");
        }

        /**
         * @param $name
         *
         * @dataProvider provider
         */
        public function testHello($name)
        {
            $this->assertEquals($this->sample->sayHello(), "Hello, $name");
        }

        public function provider()
        {
            return array(
                array("Danny"),
//                array("Something else")
            );
        }
    }
