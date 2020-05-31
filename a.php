<?php

    require foo('a.php');

    function foo(string $s) : string
    {
        assert(file_exists($s));
        return $s;
    }