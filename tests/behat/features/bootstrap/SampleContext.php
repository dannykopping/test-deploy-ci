<?php

    use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Context\BehatContext,
    Behat\Behat\Exception\PendingException;
    use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;


    require_once dirname(__FILE__)."/../../../../lib/Sample.php";

    /**
     * Features context.
     */
    class SampleContext extends BehatContext
    {
        /**
         * @var Sample
         */
        private $sample;

        private $greeting;

        /**
         * @Given /^that an instance exists$/
         */
        public function thatAnInstanceExists()
        {
            $this->sample = new Sample();
        }

        /**
         * @Given /^its name is "([^"]*)"$/
         */
        public function itsNameIs($name)
        {
            $this->sample->setName($name);
        }

        /**
         * @Given /^make it greet me$/
         */
        public function makeItGreetMe()
        {
            $this->greeting = $this->sample->sayHello();
        }

        /**
         * @Then /^it should say "([^"]*)"$/
         */
        public function itShouldSay($greeting)
        {
            if($this->greeting != $greeting)
                throw new Exception("Greeting does not match name");
        }
    }
