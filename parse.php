<?php
ini_set('display_errors', 'stderr');

function IsVar($string)
{
    if(preg_match("/^((LF|GF|TF)@[a-zA-Z_\-\$&%\*!\?][a-zA-Z_\-\$&%\*!\?0-9]*)$/", $string)) return true;
    else return false;
}
function IsSymb($string)
{
    if(preg_match("/^((LF|GF|TF)@[a-zA-Z_\-\$&%\*!\?][a-zA-Z_\-\$&%\*!\?0-9]*)$/", $string)) return 1; //value
    if(preg_match("/^(int@[+\\-0-9]+)$/", $string) ||
       preg_match("/^(bool@(true|false))$/", $string) ||
       preg_match("/^(string@(([^ \\\\])*(\\\\[0-9]{3})*)*)$/", $string) ||
       preg_match("/^(nil@nil)$/", $string)) return 2; //symb
    return 0;
}
function IsLabel($string)
{
    if(preg_match("/^([a-zA-Z_\-$&%*!?][a-zA-Z_\-$&%*!?0-9]*)$/", $string)) return true;
    else return false;
}
function IsType($string)
{
    if(($string == "int") || ($string == "string") || ($string == "bool")) return true;
    else return false;
}
function SpecChars($string)
{
    $string = str_replace("&", "&amp;", $string);
    $string = str_replace("<", "&lt;", $string);
    $string = str_replace(">", "&gt;", $string);
    return $string;
}
function PrintSymb($arg, $arg_order)
{
    if (IsSymb($arg) == 1) //var
        echo sprintf("  <arg%d type=\"var\">%s</arg%d>\n", $arg_order, $arg, $arg_order);
    else
        echo sprintf("  <arg%d type=\"%s\">%s</arg%d>\n", $arg_order, substr($arg, 0, strpos($arg, "@")), substr($arg, strpos($arg, "@") + 1), $arg_order);
}

if($argc > 1)
{
    if(($argv[1] == "--help") && ($argc == 2))
    {
        echo("Popis:\n  Skript typu filtr (parse.php v jazyce PHP 8.1) načte ze standardního vstupu zdrojový kód v IPPcode22, zkontroluje lexikální a syntaktickou správnost kódu a vypíše na standardní
výstup XML reprezentaci programu.");
        echo("Použití:\n   php parse.php <[zdrojový kód]\n   php parse.php --help\n");
        echo("Chybové návratové kódy:\n");
        echo("   21 - chybná nebo chybějící hlavička ve zdrojovém kódu zapsaném v IPPcode22\n");
        echo("   22 - neznámý nebo chybný operační kód ve zdrojovém kódu zapsaném v IPPcode22\n");
        echo("   23 - jiná lexikální nebo syntaktická chyba zdrojového kódu zapsaného v IPPcode22\n");
        exit(0);
    }else exit(10);
}

$Head = false;
$command_counter = 1;

while ($line = fgets(STDIN)) {
    if (strpos($line, "#")) $line = substr($line, 0, strpos($line, "#")); //pokud najdu # vezmu jen to před ním
    $line = preg_replace('/\s+/', ' ', $line); //zbavím se vícenásobných mezer
    $splitted = explode(' ', trim($line)); //Zbavím se mezer před, za a odřádkování
    $num_split = count($splitted);
    if($line[0] == '#') $line = " ";
    if (!ctype_space($line)) //pokud to není prázdný řádek
    {
        if (!$Head)
        {
            if (strtoupper($splitted[0]) == ".IPPCODE22") {
                $Head = true;
                if ($num_split > 1) exit(21);
                echo("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n");
                echo("<program language=\"IPPcode22\">\n");
            } else exit(21);
        } else {
            $splitted[0] = strtoupper($splitted[0]);
            switch ($splitted[0]) //rozlišení příkazu
            {
                case "MOVE": //MOVE <var> <symb>
                case "INT2CHAR": //INT2CHAR <var> <symb>
                case "STRLEN": //STRLEN <var> <symb>
                case "TYPE": //TYPE <var> <symb>
                case "NOT": //NOT <var> <symb>
                    if ($num_split != 3) exit(23);
                    if (!IsVar($splitted[1])) exit(23);
                    if (!IsSymb($splitted[2])) exit(23);
                    $splitted[1] = SpecChars($splitted[1]);
                    $splitted[2] = SpecChars($splitted[2]);
                    echo sprintf(" <instruction order=\"%d\" opcode=\"%s\">\n", $command_counter, $splitted[0]);
                    echo sprintf("  <arg1 type=\"var\">%s</arg1>\n", $splitted[1]);
                    PrintSymb($splitted[2], 2);
                    echo(" </instruction>\n");
                    break;

                case "CREATEFRAME":
                case "PUSHFRAME":
                case "POPFRAME":
                case "RETURN":
                case "BREAK":
                    if ($num_split != 1) exit(23);
                    echo sprintf(" <instruction order=\"%d\" opcode=\"%s\">\n", $command_counter, $splitted[0]);
                    echo(" </instruction>\n");
                    break;

                case "DEFVAR": //DEFVAR <var>
                case "POPS": //POPS <var>
                    if ($num_split != 2) exit(23);
                    if (!IsVar($splitted[1])) exit(23);
                    $splitted[1] = SpecChars($splitted[1]);
                    echo sprintf(" <instruction order=\"%d\" opcode=\"%s\">\n", $command_counter, $splitted[0]);
                    echo sprintf("  <arg1 type=\"var\">%s</arg1>\n", $splitted[1]);
                    echo(" </instruction>\n");
                    break;

                case "CALL": //CALL <label>
                case "LABEL": //LABEL <label>
                case "JUMP": //JUMP <label>
                    if ($num_split != 2) exit(23);
                    if (!IsLabel($splitted[1])) exit(23);
                    echo sprintf(" <instruction order=\"%d\" opcode=\"%s\">\n", $command_counter, $splitted[0]);
                    echo sprintf("  <arg1 type=\"label\">%s</arg1>\n", $splitted[1]);
                    echo(" </instruction>\n");
                    break;

                case "PUSHS": //PUSH <symb>
                case "WRITE": //WRITE <symb>
                case "EXIT": //EXIT <symb>
                case "DPRINT": //DPRINT <symb>
                    if ($num_split != 2) exit(23);
                    if (!IsSymb($splitted[1])) exit(23);
                    $splitted[1] = SpecChars($splitted[1]);
                    echo sprintf(" <instruction order=\"%d\" opcode=\"%s\">\n", $command_counter, $splitted[0]);
                    PrintSymb($splitted[1], 1);
                    echo(" </instruction>\n");
                    break;

                case "ADD": //ADD <var> <symb1> <symb2>
                case "SUB": //SUB <var> <symb1> <symb2>
                case "MUL": //MUL <var> <symb1> <symb2>
                case "IDIV": //IDIV <var> <symb1> <symb2>

                case "LT": //LT <var> <symb1> <symb2>
                case "GT": //GT <var> <symb1> <symb2>
                case "EQ": //EQ <var> <symb1> <symb2>

                case "AND": //AND <var> <symb1> <symb2>
                case "OR": //OR <var> <symb1> <symb2>

                case "STRI2INT": //STRI2INT <var> <symb1> <symb2>
                case "CONCAT": //CONCAT <var> <symb1> <symb2>
                case "GETCHAR": //GETCHAR <var> <symb1> <symb2>
                case "SETCHAR": //SETCHAR <var> <symb1> <symb2>
                    if ($num_split != 4) exit(23);
                    if (!IsVar($splitted[1])) exit(23);
                    if (!IsSymb($splitted[2])) exit(23);
                    if (!IsSymb($splitted[3])) exit(23);
                    $splitted[1] = SpecChars($splitted[1]);
                    $splitted[2] = SpecChars($splitted[2]);
                    $splitted[3] = SpecChars($splitted[3]);
                    echo sprintf(" <instruction order=\"%d\" opcode=\"%s\">\n", $command_counter, $splitted[0]);
                    echo sprintf("  <arg1 type=\"var\">%s</arg1>\n", $splitted[1]);
                    PrintSymb($splitted[2], 2);
                    PrintSymb($splitted[3], 3);
                    echo(" </instruction>\n");
                    break;

                case "READ":  //READ <var> <type>
                    if ($num_split != 3) exit(23);
                    if (!IsVar($splitted[1])) exit(23);
                    if (!IsType($splitted[2])) exit(23);
                    $splitted[1] = SpecChars($splitted[1]);
                    echo sprintf(" <instruction order=\"%d\" opcode=\"%s\">\n", $command_counter, $splitted[0]);
                    echo sprintf("  <arg1 type=\"var\">%s</arg1>\n", $splitted[1]);
                    echo sprintf("  <arg2 type=\"type\">%s</arg2>\n", $splitted[2]);
                    echo(" </instruction>\n");
                    break;

                case "JUMPIFEQ": //JUMPIFEQ <label> <symb1> <symb2>
                case "JUMPIFNEQ": //JUMPIFNEQ <label> <symb1> <symb2>
                    if ($num_split != 4) exit(23);
                    if (!IsLabel($splitted[1])) exit(23);
                    if (!IsSymb($splitted[2])) exit(23);
                    if (!IsSymb($splitted[3])) exit(23);
                    $splitted[2] = SpecChars($splitted[2]);
                    $splitted[3] = SpecChars($splitted[3]);
                    echo sprintf(" <instruction order=\"%d\" opcode=\"%s\">\n", $command_counter, $splitted[0]);
                    echo sprintf("  <arg1 type=\"label\">%s</arg1>\n", $splitted[1]);
                    PrintSymb($splitted[2], 2);
                    PrintSymb($splitted[3], 3);
                    echo(" </instruction>\n");
                    break;
                default:
                    exit(22);
            }
            $command_counter++;
        }
    }
}
echo("</program>");
if(!$Head) exit(21);
exit(0);
?>