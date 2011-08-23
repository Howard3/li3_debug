# Debugger for Lithium

This is a work in progress, any feedback is appreciated.

## Notes

This debuggers recording process is a bit heavy and will be refined shortly. A provided li3debug
.db sqlite3 file should be used if no connection details are defined,
however this functionality has not been tested yet.

## Quick Setup

Adding this library should occur immediately after Lithium is added so that it can attach to as
much as possible.

    Libraries::add('li3_debug', array(
        'connection' => array(
            'type' => 'MongoDb',
            'host' => 'localhost',
            'database' => 'li3_debug'
        ),
        'ignore' => array(
            'lithium\template\Helper',
            'lithium\template\helper\Html'
        )
    ));