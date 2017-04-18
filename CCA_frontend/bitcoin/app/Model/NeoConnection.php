<?php
/**
 * @author: Martin Očenáš - xocena04@stud.fit.vutbr.cz
 */

namespace App\Model;

use GraphAware\Neo4j\Client\ClientBuilder;

/**
 * Třída pro vytvoření spojení s Neo4j databází
 *
 * Class NeoConnection
 * @package App\Model
 */
abstract class NeoConnection
{
    /**
     * Instance klienta databáze
     * @var \GraphAware\Neo4j\Client\ClientInterface
     */
    private static $client=null;

    /**
     * Vytvoření připojení do Neo4J databáze
     * @return \GraphAware\Neo4j\Client\ClientInterface - Klient databáze
     * @throws \Exception
     */
    public static function connect()
    {
        if (self::$client == null) {
            self::$client = ClientBuilder::create()
                ->addConnection('default', env("NEO4J_DB"))
                ->setDefaultTimeout(env("DATABASE_TIMEOUT"))
                ->build();
        }

        /**
         * Samotné připojení nevaliduje, zda-li se skutečně povedlo k databázi připojit
         *
         * Tento dotaz slouží pro detekci jestli spojení k databázi skutečně funguje
         * pokud ne, bude vyhozena výjimka
         */
        self::$client->run("MATCH (n:Person) RETURN n");

        return self::$client;
    }
}