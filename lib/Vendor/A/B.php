<?php
namespace Vendor\A;

class B {
    public function talk() {
        echo __CLASS__.'->'.__METHOD__."()\n";
    }
}