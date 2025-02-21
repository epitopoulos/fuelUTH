<?php

use App\Database;

return [

    Database::class => function() {

        return new Database(host: 'localhost',
                            name: 'mydb',
                            user: 'mydbuser',
                            password: '1234');
    }

];