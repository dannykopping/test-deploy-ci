<?php

    require_once dirname(__FILE__)."/lib/Sample.php";

    $s = new Sample();
    $s->setName("Danny");
    echo $s->sayHello();