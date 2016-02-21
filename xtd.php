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

//globalni promenne - vyuziti pro zpracovani argumentu a ponechani hodnot pro dalsi praci
$countA = 0; $countB = 0; $countG = 0; $countOut = 0; $countIn = 0; $countValid = 0; $etc = 0;
$countHeader = 0; $countEtc = 0; $input = ""; $output = ""; $hlavicka = ""; $isvalid = ""; $pomocnik = "";

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
function vyrobXML($vstup) {
        // pokud je xml nevalidni (coz by se podle zadani nemelo stat) vypisi chybu 4
        $GLOBALS["input"] =  simplexml_load_string($vstup);
        if($GLOBALS["input"] === false) {
            vypisChybu("vstupformat");
        }
    zpracujXML($GLOBALS["input"]);
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
        //je zadany input param, checknu jestli soubor existuje, muzu cist a jde pro cteni..
        (file_exists($zadaneArg["input"]) && is_readable($zadaneArg["input"]))
        or vypisChybu("vstup");
        if(!($GLOBALS["input"] = file_get_contents($zadaneArg["input"])) === true) {
            vypisChybu("vstup");
        }
        $GLOBALS["countIn"]++;
    } else {
        //ulozim si stdin do stringu, pri chybe hazi false
        if(!($GLOBALS["input"] = file_get_contents("php://stdin")) === true) {
            vypisChybu("vstup");
        }
    }
    if(array_key_exists("output", $zadaneArg)) {
        $GLOBALS["countOut"]++;
        $GLOBALS["output"] = $zadaneArg["output"];
    } else {
        $GLOBALS["output"] = "php://stdout";
    }
    if(array_key_exists("header", $zadaneArg)) {
        $GLOBALS["countHeader"]++;
        $GLOBALS["hlavicka"] = $zadaneArg["header"];
    }
    if(array_key_exists("isvalid", $zadaneArg)) {
        (file_exists($zadaneArg["isvalid"]) && is_readable($zadaneArg["isvalid"]))
        or vypisChybu("vstup");
        $GLOBALS["isvalid"] = $zadaneArg["isvalid"];
    }
    if(array_key_exists("etc", $zadaneArg)) {
        //osetrim aby to bylo cele cislo a vetsi rovno 0 + osetreni aby nebyly bile znaky
        if((ctype_digit($zadaneArg["etc"])) && (!preg_match('/\s/',$zadaneArg["etc"])) && ($zadaneArg["etc"] >= 0)) {
            // etc nelze kombinovat s b !
            if(isset($zadaneArg["b"])) {
                vypisChybu("parametry");
            } else {
                $GLOBALS["etc"] = $zadaneArg["etc"];
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
    if($GLOBALS["countA"] > 1 || $GLOBALS["countB"] > 1 || $GLOBALS["countG"] > 1 || $GLOBALS["countEtc"] > 1 ||
    $GLOBALS["countIn"] > 1 || $GLOBALS["countOut"] > 1 || $GLOBALS["countHeader"] > 1 || $GLOBALS["countValid"] > 1) {
        vypisChybu("parametry");
    }
}

function zpracujXML($root, $parent="")
{
    foreach ($root as $key => $value) {
        if (zpracujXML($value, $parent . "" . $key) == 0) {
            $pom = ($parent . "" . (string)$key . "=" . trim((string)$value) . "\n");
            $GLOBALS["pomocnik"] = $pom;
        }
    }
    foreach ($root->attributes() as $attrib => $atr) {
        $GLOBALS["pomocnik"] .= "|$attrib=$atr|";
    }
    echo $GLOBALS["pomocnik"];
}
// zavolam si funkci na zpracovani vstupnich argumentu
zpracovaniVstupu($argc, $argv);
vyrobXML($GLOBALS["input"]);

?>
