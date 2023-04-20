<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Exception;

class Person
{
    //declare properties
    public string $title;
    public string $first_name;
    public string $last_name;

    //functions to add object properties
    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function setFirstName($firstName)
    {
        $this->first_name = $firstName;
    }

    public function setLastName($lastName)
    {
        $this->last_name = $lastName;
    }
}

class CsvUploader
{
    //declare properties
    private string $targetDir;
    private array $allowedFileTypes;
    private int $maxFileSize;
    public array $people;

    //functions to add object properties
    private function setTarget($target)
    {
        $this->targetDir = $target;
    }

    private function setAllowedTypes($allowTypes)
    {
        $this->allowedFileTypes = $allowTypes;
    }

    private function setMaxSize($maxSize)
    {
        $this->maxFileSize = $maxSize;
    }

    public function __construct(
        $targetDir = "/uploads/",
        $allowedFileTypes = ["csv"],
        $maxFileSize = 5000000
    ) {
        $this->setTarget($targetDir);
        $this->setAllowedTypes($allowedFileTypes);
        $this->setMaxSize($maxFileSize);
    }

    //function to upload a file and extract names from it
    public function upload($file)
    {
        // Get the file extension and the temporary file.
        $fileType = $file->getClientOriginalExtension();
        $tempFile = $file;

        // Allow only allowed file types
        if (!in_array($fileType, $this->allowedFileTypes)) {
            throw new Exception(
                "Sorry, only " .
                    implode(", ", $this->allowedFileTypes) .
                    " files are allowed."
            );
        }

        // Check file size
        if ($file->getSize() > $this->maxFileSize) {
            throw new Exception("Sorry, your file is too large.");
        }

        // Open the file and extract the names.
        $file = fopen($tempFile, "r");

        //if the file exists
        if ($file) {
            //declare array variable
            $allNames = [];
            //loop through the rows
            while (($row = fgetcsv($file)) !== false) {
                $names = explode(",", $row[0]);

                foreach ($names as $name) {
                    //add all names to the array
                    array_push($allNames, $name);
                }
            }

            $this->people = $allNames;

            fclose($file);
        }
    }

    public function parseNames()
    {
        //get a list of all names
        $list = $this->people;
        $completeList = [];
        //loop through each name set
        foreach ($list as $l) {
            if ($l == "homeowner") {
                //if homeowner skip to the next loop
                continue;
            }

            //check to see if multiple names within the $l variable
            if (str_contains($l, "and") || str_contains($l, "&")) {
                $person1 = new Person();
                $person2 = new Person();
                //create array from multiple names in $l
                if (str_contains($l, "and")) {
                    $overallArray = explode("and", $l);
                } else {
                    $overallArray = explode("&", $l);
                }

                //remove whitespace
                $overallArray = array_map("trim", $overallArray);
                $array1 = explode(" ", $overallArray[0]);
                $array2 = explode(" ", $overallArray[1]);

                $length1 = count($array1);
                $length2 = count($array2);

                //check against usual input styles eg Mr & Mrs Jon Smith
                if ($length1 == "1" && $length2 == "3") {
                    $person1->setTitle($array1[0]);
                    $person1->setFirstName($array2[1]);
                    $person1->setLastName($array2[2]);
                    $person2->setTitle($array2[0]);
                    $person2->setFirstName("empty");
                    $person2->setLastName($array2[2]);
                    //check against usual input styles eg Mr & Mrs Smith
                } elseif ($length1 == "1" && $length2 == "2") {
                    $person1->setTitle($array1[0]);
                    $person1->setFirstName("empty");
                    $person1->setLastName($array2[1]);
                    $person2->setTitle($array2[0]);
                    $person2->setFirstName("empty");
                    $person2->setLastName($array2[1]);
                } else {
                    //if the inputs dont follow usual input styles
                    switch ($length1) {
                        case "1":
                            $person1->setTitle($array1[0]);
                            break;
                        case "2":
                            $person1->setTitle($array1[0]);
                            $person1->setFirstName($array1[1]);
                            break;
                        case "3":
                            $person1->setTitle($array1[0]);
                            $person1->setFirstName($array1[1]);
                            $person1->setLastName($array1[2]);
                            break;
                        default:
                            throw new Exception(
                                "Sorry, there was an error processing your data."
                            );
                            break;
                    }

                    switch ($length2) {
                        case "1":
                            $person2->setTitle($array2[0]);
                            break;
                        case "2":
                            $person2->setTitle($array2[0]);
                            $person2->setFirstName($array2[1]);
                            break;
                        case "3":
                            $person2->setTitle($array2[0]);
                            $person2->setFirstName($array2[1]);
                            $person2->setLastName($array2[2]);
                            break;

                        default:
                            throw new Exception(
                                "Sorry, there was an error processing your data."
                            );
                            break;
                    }
                }
                //add individual names to the complete list array
                array_push($completeList, $person1, $person2);
            } else {
                //if there is only one name in the $l variable
                $person = new Person();
                $overallArray = explode(" ", $l);
                $length = count($overallArray);

                switch ($length) {
                    case "2":
                        $person->setTitle($overallArray[0]);
                        $person->setFirstName("empty");
                        $person->setLastName($overallArray[1]);
                        break;
                    case "3":
                        $person->setTitle($overallArray[0]);
                        $person->setFirstName($overallArray[1]);
                        $person->setLastName($overallArray[2]);
                        break;
                    default:
                        throw new Exception(
                            "Sorry, there was an error processing your data."
                        );
                        break;
                }
                array_push($completeList, $person);
            }
        }
        return $completeList;
    }
}

class FormController extends Controller
{
    function getData(Request $req)
    {
        //if file is received
        if (!empty($req->file("csvfile"))) {
            try {
                //create new CsvUploader, process data and parse names
                $csvUploader = new CsvUploader();
                $csvUploader->upload($req->file("csvfile"));
            } catch (Exception $e) {
                return $e->getMessage();
            }
            return $individuals = $csvUploader->parseNames();
        }
    }
}
