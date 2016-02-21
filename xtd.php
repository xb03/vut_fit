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
$countHeader = 0; $countEtc = 0; $input = ""; $output = ""; $hlavicka = ""; $isvalid = ""; $DDL = array();

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

function checkObsahy($tabulky) {
    global $DDL;
    //otestuji jestli ma nejaky obsah
    if(trim($tabulky) == "") {
        $DDL[$tabulky->getName()] = $DDL[$tabulky->getName()];
    } else {
        //pokud obsah ma tak si overim jestli tam uz value neni
        if(strpos($DDL[$tabulky->getName()], "value BIT") !== false) {
            $updatedSQL = str_replace("value BIT", prioritaTypu("BIT", urciTyp(strtolower(trim($tabulky)), "obsah")),$DDL[$tabulky->getName()]);
            $DDL[$tabulky->getName()] = $updatedSQL;
        } else if(strpos($DDL[$tabulky->getName()], "value INT") !== false) {
            $updatedSQL = str_replace("value INT", prioritaTypu("INT", urciTyp(strtolower(trim($tabulky)), "obsah")),$DDL[$tabulky->getName()]);
            $DDL[$tabulky->getName()] = $updatedSQL;
        } else if(strpos($DDL[$tabulky->getName()], "value FLOAT") !== false) {
            $updatedSQL = str_replace("value FLOAT", prioritaTypu("FLOAT", urciTyp(strtolower(trim($tabulky)), "obsah")),$DDL[$tabulky->getName()]);
            $DDL[$tabulky->getName()] = $updatedSQL;
        } else if(strpos($DDL[$tabulky->getName()], "value NVARCHAR") !== false) {
            $updatedSQL = str_replace("value NVARCHAR", prioritaTypu("NVARCHAR", urciTyp(strtolower(trim($tabulky)), "obsah")),$DDL[$tabulky->getName()]);
            $DDL[$tabulky->getName()] = $updatedSQL;
        } else if(strpos($DDL[$tabulky->getName()], "value NTEXT") !== false) {
            $updatedSQL = str_replace("value NTEXT", prioritaTypu("NTEXT", urciTyp(strtolower(trim($tabulky)), "obsah")),$DDL[$tabulky->getName()]);
            $DDL[$tabulky->getName()] = $updatedSQL;
        } else {
            //kdyz tam value jeste neni tak ho tam vytvorim
            $DDL[$tabulky->getName()] .= ", value ".urciTyp(strtolower(trim($tabulky)), "obsah");
        }
    }
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

function urciTyp($hodnota, $typ) {
    if($typ == "obsah") {
        if ($hodnota === "0" || $hodnota === "1" || $hodnota == "true" || $hodnota == "false") {
            return "BIT";
        } else if (is_numeric($hodnota)) {
            if(strpos($hodnota, ".") || strpos($hodnota, "e") || strpos($hodnota, "f") || strpos($hodnota, "E") || strpos($hodnota, "F")) {
                $pom = floatval($hodnota);
            } else {
                $pom = intval($hodnota);
            }
            if (is_int($pom)) {
                return "INT";
            } else {
                return "FLOAT";
            }
        } else {
            return "NTEXT";
        }
    } else if($typ == "atribut") {
        //atributu
    } else {
        // podelementu
    }
}
function prioritaTypu($puvodni, $novy) {
    switch($puvodni) {
        case "BIT":
            switch($novy) {
                case "BIT":
                    return "value BIT";
                    break;
                default:
                    return "value $novy";
            }
            break;
        case "INT":
            switch($novy) {
                case "BIT":
                case "INT":
                    return "value INT";
                default:
                    return "value $novy";
            }
            break;
        case "FLOAT":
            switch($novy) {
                case "BIT":
                case "INT":
                case "FLOAT":
                    return "value FLOAT";
                default:
                    return "value $novy";
            }
            break;
        case "NVARCHAR":
            switch($novy) {
                case "NTEXT":
                    return "value $novy";
                default:
                    return "value NVARCHAR";
            }
            break;
        case "NTEXT":
            return "value NTEXT";
            break;
    }
}

function zpracujXML($root) {
    /**
     * Globalni pole, ve kterem budu mit ulozene veskere DDL queries..
     * Tvar $DDL:
     * $DDL[tabulka]
     * [tabulka] = nazvy jednotlivych elementu (vzniknou z nich tabulky)
     * - pro kazdou tabulku je pote hodnota obsahujici SQL
     */
    global $DDL;
    //projedu vsechny elementy a ulozim si je jako objekty $tabulky
    foreach($root as $tabulky) {
        //pro praci v poli potrebuju stringy, takze ziskam jmeno pres getName
        if(!(array_key_exists($tabulky->getName(), $DDL))) {
            //pokud neexistuje tak vytvorim prikaz pro CREATE TABLE + primarni klic
            $prikaz = "CREATE TABLE " . strtolower($tabulky->getName()) . " ( PRK_" . strtolower($tabulky->getName()) . "_ID INT PRIMARY KEY";
            // do DDL si ulozim prikaz
            $DDL[$tabulky->getName()] = $prikaz;
            }
            checkObsahy($tabulky);
            //***********************************************
                foreach($root->attributes() as $atributy) {
                    
                }
            //***********************************************
        if($tabulky->count() !== 0) {
            zpracujXML($tabulky);
        }
    }
}

// zavolam si funkci na zpracovani vstupnich argumentu
zpracovaniVstupu($argc, $argv);
vyrobXML($GLOBALS["input"]);
print_r($DDL);
?>
