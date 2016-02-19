<?php

#XTD:xbartu03

/**
 * Projekt: IPP 1. XTD
 * Autor: Tomas Bartu
 * Prevod XML na DDL
 */

/**
 * Funkce pro vytisknuti napovedy pri pouziti parametru --help
 */
function helpTisk() {
    echo ".::Napoveda::.\n" .
         "Autor - Tomas Bartu [xbartu03]\n" .
         "Pouziti - xtd.php [--parametry] [--input] [--output]\n" .
         "Parametry - a,b,g,input,output\n";
}

//globalni promenne - vyuziti pro zpracovani argumentu
$countA = 0;
$countB = 0;
$countG = 0;
$countOut = 0;
$countIn = 0;
$countValid = 0;
$countHeader = 0;
$countEtc = 0;

/**
 * Funkce pro vypis chyby a ukonceni programu spravnym navratovym kodem
 * @param $type - Typ chyby, podle ktereho se nasledne vyhodi exit code
 */
function vypisChybu($type) {
    $ex = 0;
    switch($type) {
        case "parametry":
            fprintf(STDERR, "Nastala chyba pri zadani parametru.\n");
            $ex = 1;
            break;
        case "vstup":
            fprintf(STDERR, "Nastala chyba pri otevreni vstupniho souboru.\n");
            $ex = 2;
            break;
        case "vystup":
            fprintf(STDERR, "Nastala chyba pri vytvoreni/prepsani vystupniho souboru.\n");
            $ex = 3;
            break;
        case "vstupformat":
            fprintf(STDERR, "Spatny format vstupniho souboru.\n");
            $ex = 4;
            break;
        case "konflikt":
            fprintf(STDERR, "Nastal konflikt.\n");
            $ex = 90;
            break;
        case "overeni":
            fprintf(STDERR, "XML is not valid.\n");
            $ex = 91;
            break;
    }
    //zavru stream
    fclose(STDERR);
    exit($ex);
}

/**
 * Funkce, pomoci ktere zpracuji zadane parametry a overim zda jsou korektni
 * Je volana jako prvni, veskera dalsi cinnost probiha az po jejim uspesnym probehnuti
 */
function zpracovaniVstupu($argc, $argv) {
    // Povolene prepinace - vyuziji ve funkci getopt
    $kratky = "abg";
    $dlouhy = array(
        "help::",
        "output:",
        "input:",
        "etc:",
        "isvalid:",
        "header:",
    );
    //Pouziti funkce getopt pro zpracovani parametru - pole[parametr] = obsah
    $zadaneArg = getopt($kratky, $dlouhy);
    //Zjisteni zda existuje key s nazvem help + obdobne i pro ostatni parametry.
   if(array_key_exists("a", $zadaneArg)) {
       //osetreni aby mohlo byt zadano opravdu jen -a (getopt bere i -blabla)
       if(in_array("-a", $argv)) {
           $GLOBALS["countA"]++;
       } else {
           vypisChybu("parametry");
       }
   }
    if(array_key_exists("b", $zadaneArg)) {
        if(in_array("-b", $argv)) {
            $GLOBALS["countB"]++;
        } else {
            vypisChybu("parametry");
        }
    }
    if(array_key_exists("g", $zadaneArg)) {
        if(in_array("-g", $argv)) {
            $GLOBALS["countG"]++;
        } else {
            vypisChybu("parametry");
        }
    }
    if(array_key_exists("input", $zadaneArg)) {

    }
    if(array_key_exists("output", $zadaneArg)) {

    }
    if(array_key_exists("header", $zadaneArg)) {

    }
    if(array_key_exists("isvalid", $zadaneArg)) {
        vypisChybu("overeni");
    }
    if(array_key_exists("etc", $zadaneArg)) {
        //osetrim aby to bylo cele cislo a vetsi rovno 0 + osetreni aby nebyly bile znaky
        if((ctype_digit($zadaneArg["etc"])) && (!preg_match('/\s/',$zadaneArg["etc"])) && ($zadaneArg["etc"] >= 0)) {
            // etc nelze kombinovat s b !
            if(isset($zadaneArg["b"])) {
                vypisChybu("parametry");
            } else {
                $GLOBALS["countEtc"]++;
            }
        } else {
            vypisChybu("parametry");
        }
    }
    if(array_key_exists("help", $zadaneArg)) {
        //Help muze byt zadan pouze samotny, nelze jej kombinovat
        if ($argc == 2) {
            // osetreni aby slo samotne --help a neslo pridat treba --help=2
            if($zadaneArg["help"] <> false) {
                vypisChybu("parametry");
            }
            helpTisk();
            exit(0);
        } else {
            vypisChybu("parametry");
        }
    }
    //pokud nesouhlasi pocty argumentu tak fail
    if($argc <> sizeof($zadaneArg) + 1) {
        vypisChybu("parametry");
    }
    //overim jestli vse bylo zadano pouze jednou
    if($GLOBALS["countA"] > 1 || $GLOBALS["countB"] > 1 | $GLOBALS["countG"] > 1 || $GLOBALS["countEtc"] > 1) {
        vypisChybu("parametry");
    }
}

zpracovaniVstupu($argc, $argv);
?>
