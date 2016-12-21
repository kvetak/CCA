<?php
/**
 * Author: Martin Očenáš
 * Date: 20.11.16
 * Time: 15:33
 *
 * Contains definition all of exceptions that can occur in Blockchain library
 */

namespace App\Model\Blockchain\Parser;

/**
 * Class BlockchainException
 * @package BlockChainParser
 *
 * Abstract exception, parent of all exceptions that happen in Blockchain library
 */
abstract class BlockchainException extends \Exception
{

}

class FileSeekException extends BlockchainException
{}

class FileOpenException extends BlockchainException
{}

class FileSizeException extends BlockchainException
{}

class FileCloseException extends BlockchainException
{}

class FileReadException extends BlockchainException
{}

