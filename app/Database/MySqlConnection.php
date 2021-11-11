<?php

namespace App\Database;

class MySqlConnection extends \Illuminate\Database\MySqlConnection
{
    protected function getDefaultQueryGrammar()
    {
        return $this->withTablePrefix(new MySqlQuerier);
    }

    protected function getDefaultSchemaGrammar()
    {
        return $this->withTablePrefix(new MySqlSchemar);
    }
}
