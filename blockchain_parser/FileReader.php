<?php
/**
 * User: Martin Očenáš - xocena04
 * Date: 20.11.16
 * Time: 14:57
 */

namespace App\Model\Blockchain\Parser;

/**
 * Class FileReader
 * @package BlockChainParser
 *
 * Class for reading block files from blockchain.
 *
 * Blockchain is stored in a lot of files, this class creates abstraction,
 * that read bytes are one continuous stream.
 */
class FileReader
{
    /**
     * @var String - path to blockchain directory, where block files are stored
     */
    private $blockchainDir;

    /**
     * @var resource - Handle for current file
     */
    private $fileHandle;

    /**
     * @var String - name of current processed file
     */
    private $filename;

    /**
     * @var Integer - number of bytes remaining form current file
     */
    private $remainingBytes;

    /**
     * FileReader constructor.
     *
     * @param String $blockchainDir - path to blockchain directory, where block files are stored, including trailing slash
     */
    public function __construct($blockchainDir)
    {
        $this->blockchainDir = $blockchainDir;
        $this->fileHandle=null;
        $this->filename=null;
        $this->remainingBytes=0;
    }

    /**
     * @brief Set position in blockchain from where, the parsing should happen
     * @param PositionDto $positionDto - position in blockchain
     *
     * Open given file and set offset in file, to given position
     */
    public function setPosition(PositionDto $positionDto)
    {
        $this->openFile($positionDto->getFilename());
        $this->skipBytes($positionDto->getPosition());
    }

    /**
     * @brief Return current position in blockchain
     * @return PositionDto - Current position in blockchain
     */
    public function getPosition()
    {
        return new PositionDto($this->filename,ftell($this->fileHandle));
    }

    /**
     * @brief Read number of bytes from input file
     * @param Integer $count - number of bytes to read
     *
     * @return String - read bytes
     *
     * @throws FileReadException - If any time fails to read from file
     */
    public function readBytes($count)
    {
        if ($this->remainingBytes >= $count)
        {
            return $this->readFromFile($count);
        }
        echo "out of file".PHP_EOL;

        $output=$this->readFromFile($this->remainingBytes);
        $still_to_read=$count-$this->remainingBytes;
        $this->openNextFile();
        return $output.$this->readFromFile($still_to_read);
    }

    /**
     * @brief Read several bytes from input
     * @param Integer $count - number of bytes to read
     * @return string - read content
     * @throws FileReadException - If read failed
     *
     * Read bytes, check if read was successful
     */
    private function readFromFile($count)
    {
        if (($output=fread($this->fileHandle,$count)) === FALSE)
        {
            throw new FileReadException("Unable to read data from file ".$this->filename);
        }
        $this->remainingBytes-=$count;
        return $output;
    }


    /**
     * @brief Skip number of bytes in current file
     * @param Integer $count - Number of bytes to skip
     *
     * @throws FileSeekException - If fseek system call fails
     */
    private function skipBytes($count)
    {
        if ($this->remainingBytes >= $count)
        {
            if (fseek($this->fileHandle,$count,SEEK_CUR) == -1)
            {
                throw new FileSeekException("Failed to skip bytes in file ".$this->filename);
            }
        }
        else
        {
            $toSkip=$count-$this->remainingBytes; //bytes that remain to skip in next file
            $this->openNextFile();
            $this->skipBytes($toSkip);
        }
    }

    /**
     * @brief After one file is all read, open next file in blockchain
     * Determine filename of next block file and open in
     */
    private function openNextFile()
    {
        if ($this->filename == NULL)
        {
            $filename=$this->generateFileName(0);
        }
        else
        {
            $current_file_number=substr($this->filename,3,5);
            $filename=$this->generateFileName($current_file_number+1);
        }
        $this->openFile($filename);
    }

    /**
     * @brief Generate name of block file from given number
     * @param Integer $fileNumber Number of dat file, which name is to generate
     * @return String - name of file
     */
    private function generateFileName($fileNumber)
    {
        return "blk".sprintf("%05d",$fileNumber).".dat";
    }

    /**
     * @brief Open given file
     * @param String $filename - file to open
     *
     * Before opening new file, close current file (if any is)
     * @throws FileCloseException - If a file is already opened and is not able to close it
     * @throws FileOpenException - If new file cannot be opened
     * @throws FileSizeException - If not possible to determine size of new file
     */
    private function openFile($filename)
    {
        if ($this->fileHandle != null)
        {
            if (!fclose($this->fileHandle))
            {
                throw new FileCloseException("Unable to close file ".$this->filename);
            }
        }

        $path=$this->blockchainDir.$filename;
        $handle=fopen($path,"r");
        if ($handle === FALSE)
        {
            throw new FileOpenException("Unable to open file ".$filename);
        }
        $this->fileHandle=$handle;
        $this->remainingBytes=filesize($path);
        if ($this->remainingBytes === FALSE)
        {
            throw new FileSizeException("Unable to determine size of file ".$filename);
        }
        $this->filename=$filename;
    }

}