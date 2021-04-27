<?php

use Emergence\Connectors\Mapping;


// skip conditions
if (!static::tableExists(Mapping::$tableName)) {
    printf("Skipping migration because table `%s` does not exist yet\n", Mapping::$tableName);
    return static::STATUS_SKIPPED;
}


// migration
DB::nonQuery(
    'UPDATE `%s` SET Connector = "gsuite" WHERE Connector = "google-apps"',
    Mapping::$tableName
);
$affectedRows = DB::affectedRows();
printf(
    "Changed column `Connector` from 'google-apps' to 'gsuite' in %u rows\n",
    $affectedRows
);


// done
return $affectedRows > 0 ? static::STATUS_EXECUTED : static::STATUS_SKIPPED;
