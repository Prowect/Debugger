<?php

/**
 * Created by Prowect
 * Author: Raffael Kessler
 * Date: 03.04.2016 - 18:00.
 * Copyright Prowect.
 */

namespace Drips\Debugger;

use Drips\Utils\OutputBuffer;

/**
 * Class Debugger.
 *
 * Dient zum Debuggen von PHP-Anwendungen. Erzeugt eine formatierte Ausgabe mit
 * Fehlermeldungen, sofern diese vom Handler erfasst wurden.
 */
class Debugger
{
    /**
     * Beinhaltet ein OutputBuffer-Objekt zum buffern der Ausgabe
     *
     * @var OutputBuffer
     */
    protected $buffer;

    /**
     * Beinhaltet ob der Debugger (bzw. die Debugseite) angezeigt werden soll oder nicht
     * @var [type]
     */
    protected $enabled = true;

    /**
     * Erzeugt eine neue Debugger-Instanz und registriert Error- und Exception-Handler
     */
    public function __construct()
    {
        $this->enable();
        $this->buffer = new OutputBuffer;
        $this->buffer->start();
    }

    /**
     * Aktiviert den Debugger bzw. die Debugseite
     */
    public function enable()
    {
        set_error_handler([Handler::class, "handleError"]);
        set_exception_handler([Handler::class, "handleException"]);
        $this->enabled = true;
    }

    /**
     * Deaktiviert den Debugger bzw. die Debugseite
     */
    public function disable()
    {
        restore_error_handler();
        restore_exception_handler();
        $this->enabled = false;
    }

    /**
     * Aktiviert PHP-Fehlermeldungen
     */
    public function enableErrors()
    {
        error_reporting(E_ALL);
        ini_set("display_errors", "on");
        ini_set("display_startup_errors", "on");
    }

    /**
     * Deaktiviert PHP-Fehlermeldungen
     */
    public function disableErrors()
    {
        $this->disable();
        error_reporting(0);
        ini_set("display_errors", "off");
        ini_set("display_startup_errors",  "off");
    }

    /**
     * Gibt zurück, ob der Debugger bzw. die Debugseite aktiviert ist
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * Erzeugt einen Codeausschnitt einer Datei und hebt eine bestimmte Zeile hervor
     *
     * @param string $path Dateipfad
     * @param int $line Zeilenzahl die hervorgehoben werden soll
     *
     * @return string
     */
    public static function getCode($path, $line)
    {
        $snippet = 7;
        $code = str_replace('>', '&gt', str_replace('<', '&lt;', file_get_contents($path)));
        $codesplit = explode("\n", $code);
        $lines = count($codesplit);
        $from = $line < $snippet ? 0 : $line - $snippet;
        $to = $lines < $line + $snippet ? $lines + 1 : $line + $snippet;
        $new = array();
        for ($i = $from; $i < $to - 1; $i++) {
            if ($i + 1 == $line) {
                $new[] = "<span class='highlight'>".($i + 1).' '.$codesplit[$i].'</span>';
            } else {
                $new[] = ($i + 1).' '.$codesplit[$i];
            }
        }
        return '<pre><code>'.implode("\n", $new)."\n</code></pre>";
    }

    /**
     * Erzeugt die Fehlerseite mit Auflistung der einzelnen Fehlermeldungen
     */
    public function __destruct()
    {
        $this->buffer->end();
        if(Handler::hasErrors() && PHP_SAPI == "cli"){
            foreach(Handler::getErrors() as $error){
                if($error["isException"]){
                    echo "[".get_class($error["context"])."]: ";
                } else {
                    echo "[ERROR#".$error["number"]."]: ";
                }
                echo $error["desc"].PHP_EOL."\t".$error["file"].":".$error["line"].PHP_EOL;
            }
        } elseif(Handler::hasErrors() && $this->isEnabled()){
            require_once __DIR__.'/layout.phtml';
        } else {
            echo $this->buffer->getContent();
        }
    }
}
