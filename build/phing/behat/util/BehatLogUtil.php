<?php
    /**
     *  This class analyzes generated jUnit files to produce Behat results
     */
    class BehatLogUtil
    {
        private static $logsPath;

        public static function setLogsPath($path)
        {
            self::$logsPath = $path;
        }

        public static function analyze()
        {
            if(empty(self::$logsPath) || !realpath(self::$logsPath))
                throw new Exception("Invalid logs path");

            $files = scandir(self::$logsPath);
            if(empty($files))   return;

            $valid = array();
            foreach($files as $file)
            {
                if(substr(strtolower($file), -3) != "xml")
                    continue;

                $valid[] = self::$logsPath.DIRECTORY_SEPARATOR.$file;
            }

            $result = array();

            $features = 0;
            $scenarios = 0;
            foreach($valid as $file)
            {
                $xml = file_get_contents($file);
                if(!empty($xml))
                {
                    $features++;
                    $scenarios += self::parse($xml);
                }

                // remove file after being processed
                @unlink($file);
            }

            return array("features" => $features, "scenarios" => $scenarios);
        }

        private static function parse($xml)
        {
            try
            {
                libxml_use_internal_errors(true);
                $element = new SimpleXMLElement($xml);
            }
            catch(Exception $e)
            {
                return;
            }

            $failures = (int) $element["failures"];
            $tests = (int) $element["tests"];
            $time = (int) $element["time"];
            $featureName = trim($element["name"]);
            $file = trim($element["file"]);

            $scenarios = 0;
            if($element->testcase)
            {
                // check each testcase for failures
                foreach($element->testcase as $testcase)
                {
                    $scenarios++;

                    // fail with details
                    if(isset($testcase->failure))
                    {
                        $scenario = trim($testcase["name"]);
                        $message = trim($testcase->failure["message"]);
                        $type = trim($testcase->failure["type"]);

                        throw new Exception("Scenario \"$scenario\" $type: $message");
                    }
                }
            }

            return $scenarios;
        }
    }
