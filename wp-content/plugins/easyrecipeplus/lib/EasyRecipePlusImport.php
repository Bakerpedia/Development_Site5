<?php

/*
    Copyright (c) 2010-2013 Box Hill LLC

    All Rights Reserved

    No part of this software may be reproduced, copied, modified or adapted, without the prior written consent of Box Hill LLC.

    Commercial use and distribution of any part of this software is not allowed without express and prior written consent of Box Hill LLC.

*/



class EasyRecipePlusImport {
    private $result;
    private $version;
    private $source;

    private $filePath;

    private $fluff;
    const MM_NONE = 0;
    const MM_STARTED = 1;
    const MM_HEADER = 2;
    const MM_INGREDIENTS = 3;
    const MM_INGREDIENTSDONE = 4;
    const MM_FINISHED = 5;

    /**
     * @param null $filePath Only used for unit tests
     */
    function __construct($filePath = null) {
        $this->version = EasyRecipePlus::$pluginVersion;
        $this->filePath = $filePath;
        /**
         * These are some of the fluff we recognise at the end of a recipe
         *
         * This isn't by any means an exhaustive list - just what we've seen in sample inputs
         * These may also prevent proper parsing if they appear somewhere unexpected in the recipe
         */
        $this->fluff = 'Calories per|source:|typed for you|NYC Nutrilink|from:|Contributor:|From the database|';
        $this->fluff .= 'Creative Cooking:|MC-Recipe Digest|File ftp:|Recipe Number:|Date:|Favorite recipe from|';
        $this->fluff .= 'MM-RECIPES@|MEAL-MASTER RECIPES LISTSERVER|From the MealMaster|Posted to|Recipe By|Shared by|';
        $this->fluff .= 'PER SERVING:|From Gemini|REC.FOOD.RECIPES|Yield:|Downloaded from|Copyright|Adapted from|';
        $this->fluff .= '~End Recipe|\* Origin:';

    }

    /**
     * Get the uploaded file allowing for it to be chunked
     * Adapted from the plupload example at https://github.com/moxiecode/plupload/blob/master/examples/upload.php
     *
     * TODO - This is for plupload 1.5.x - make sure it gets updated when WP updates to plupload v2
     *
     * @return string The filename of a complete input file.  Only returns when the file has been completely uploaded
     */
    private function plupload() {
        @set_time_limit(5 * 60);

        $targetDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "ERIUpload";

        $cleanupTargetDir = true;
        $maxFileAge = 5 * 3600;

        if (!file_exists($targetDir)) {
            @mkdir($targetDir);
        }

        $chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
        $chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 0;
        $fileName = isset($_REQUEST["name"]) ? $_REQUEST["name"] : '';


        $fileName = preg_replace('/[^\w\._]+/', '_', $fileName);

        /**
         * Make sure the fileName is unique but only if chunking is disabled
         */
        if ($chunks < 2 && file_exists($targetDir . DIRECTORY_SEPARATOR . $fileName)) {
            $ext = strrpos($fileName, '.');
            $fileName_a = substr($fileName, 0, $ext);
            $fileName_b = substr($fileName, $ext);

            $count = 1;
            while (file_exists($targetDir . DIRECTORY_SEPARATOR . $fileName_a . '_' . $count . $fileName_b))
                $count++;

            $fileName = $fileName_a . '_' . $count . $fileName_b;
        }

        $filePath = $targetDir . DIRECTORY_SEPARATOR . $fileName;

        /**
         * Create target dir
         */
        if (!file_exists($targetDir)) {
            @mkdir($targetDir);
        }

        /**
         * Remove old temp files
         */
        if ($cleanupTargetDir && is_dir($targetDir) && ($dir = opendir($targetDir))) {
            while (($file = readdir($dir)) !== false) {
                $tmpfilePath = $targetDir . DIRECTORY_SEPARATOR . $file;

                /**
                 * Remove temp file if it is older than the max age and is not the current file
                 */
                if (preg_match('/\.part$/', $file) && (filemtime($tmpfilePath) < time() - $maxFileAge) && ($tmpfilePath != "{$filePath}.part")) {
                    @unlink($tmpfilePath);
                }
            }

            closedir($dir);
        }


        /**
         * Look for the content type header
         */
        $contentType = '';
        if (isset($_SERVER["HTTP_CONTENT_TYPE"])) {
            $contentType = $_SERVER["HTTP_CONTENT_TYPE"];
        }
        if (isset($_SERVER["CONTENT_TYPE"])) {
            $contentType = $_SERVER["CONTENT_TYPE"];
        }

        /**
         * Handle non multipart uploads. Older WebKit versions didn't support multipart in HTML5
         */
        if (strpos($contentType, "multipart") !== false) {
            if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
                $out = fopen("{$filePath}.part", $chunk == 0 ? "wb" : "ab");
                if ($out) {
                    $in = fopen($_FILES['file']['tmp_name'], "rb");

                    if ($in) {
                        while ($buff = fread($in, 4096))
                            fwrite($out, $buff);
                    } else
                        die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
                    fclose($in);
                    fclose($out);
                    @unlink($_FILES['file']['tmp_name']);
                } else
                    die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
            } else
                die('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}, "id" : "id"}');
        } else {
            $out = fopen("{$filePath}.part", $chunk == 0 ? "wb" : "ab");
            if ($out) {
                $in = fopen("php://input", "rb");

                if ($in) {
                    while ($buff = fread($in, 4096))
                        fwrite($out, $buff);
                } else
                    die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');

                fclose($in);
                fclose($out);
            } else
                die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
        }

        /**
         * Return the complete file's path if all finished & OK
         */
        if (!$chunks || $chunk == $chunks - 1) {
            rename("{$filePath}.part", $filePath);
            return $filePath;
        }


        /**
         * Else just die
         */
        die('{"jsonrpc" : "2.0", "result" : null, "id" : "id"}');

    }

    /**
     * Entry point into the imports
     *
     * @return stdClass Object containing the details of the temp file containing the decoded recipe(s)
     */
    public function validate() {

        /**
         * Get the input file (unless we passed it in the constructor)
         * plupload() will handle chunked uploads and will only return when a complete file has been received
         */
        if ($this->filePath == null) {
            $this->filePath = $this->plupload();
        }

        $this->result = new stdClass();
        $this->result->status = 'FAIL';
        $this->source = isset($_POST['source']) ? $_POST['source'] : '';
        switch ($this->source) {
            case 'mastercook' :
                $this->mastercook();
                break;

            case 'mastercookmxp' :
                $this->mastercookmxp();
                break;

            case 'mgourmet3' :
            case 'mgourmet4' :
                $this->macgourmet();
                break;

            case 'yummysoup' :
                $this->yummysoup();
                break;

            case 'paprika' :
                $this->paprika();
                break;

            case 'paprikas' :
                $this->paprika(true);
                break;

            case 'mealmaster' :
                $this->mealmaster();
                break;

            case 'cookn' :
                $this->cookn();
                break;

            case 'cookbookwiz' :
                $this->cookbookwiz();
                break;

            default :
                $this->result->error = "Oops!  I don't recognise the input format";
                break;
        }

        return $this->result;
    }

    function createPosts() {
        $result = new stdClass();
        $result->status = 'FAIL';
        if (!isset($_POST['files']) || !is_array($_POST['files'])) {
            $result->error = "No files found";
            return $result;
        }
        foreach ($_POST['files'] as $file) {
            $s = @file_get_contents($file);
            if ($s === false) {
                $result->error = "Can't read " . $file;
                return $result;
            }
            $recipes = @unserialize($s);
            if ($recipes === false) {
                $result->error = "Error while unserializing " . $file;
                return $result;
            }

            if (!is_array($recipes)) {
                $result->error = "Problem parsing " . $file;
                return $result;
            }
            foreach ($recipes as $recipe) {
                $post = array();
                if (empty($recipe->author)) {
                    $recipe->author = '';
                }
                if (empty($recipe->name)) {
                    $recipe->name = '';
                }

                $post['post_author'] = wp_strip_all_tags($recipe->author);
                $post['post_title'] = wp_strip_all_tags($recipe->name);
                $template = new EasyRecipePlusTemplate(dirname(__FILE__) . "/../templates/easyrecipe-template.html");
                /**
                 * Clean out optional data
                 */
                foreach (array("name", "type", "cuisine", "author", "preptime", "cooktime", "yield", "summary") as $field) {
                    if (empty($recipe->$field)) {
                        unset($recipe->$field);
                    }
                }
                $post['post_content'] = $template->replace($recipe);
                /**
                 * Post authors from imports are meaningless in the post_author context
                 * Make sure it's not set so wp_insert_post will use the current user's ID
                 */
                unset($post['post_author']);
                wp_insert_post($post, true);
                // TODO - handle errors
            }
            @unlink($file);
        }
        $result->status = 'OK';
        return $result;
    }

    /**
     *
     * @param array $recipes
     */
    private function writeTemp($recipes) {
        $tmpName = @tempnam('', 'ERI');

        $fp = @fopen($tmpName, "w");
        @fputs($fp, serialize($recipes));

        @fclose($fp);
        @chmod($tmpName, 0666);
        $this->result->status = 'OK';
        $this->result->nRecipes = count($recipes);
        $this->result->file = $tmpName;
    }

    /**
     * Parses a MasterCook html recipe
     *
     * @param DOMElement $recipeElement
     * @return stdClass
     */
    private function parseMastercookRecipe(DOMElement $recipeElement) {
        /** @var ImportRecipe $recipe */
        $recipe = new stdClass();

        $recipe->version = $this->version;

        $recipe->name = trim($recipeElement->getAttribute('name'));
        $recipe->author = trim($recipeElement->getAttribute('author'));
        $serves = $recipeElement->getElementsByTagName('Serv');
        if ($serves->length > 0) {
            /** @noinspection PhpUndefinedMethodInspection */
            $recipe->yield = trim($serves->item(0)->getAttribute('qty'));
        }
        $prepTime = $recipeElement->getElementsByTagName('PrpT');
        if ($prepTime->length > 0) {
            /** @noinspection PhpUndefinedMethodInspection */
            $prepTime = trim($prepTime->item(0)->getAttribute('elapsed'));
            if (preg_match('/^(\d+):(\d+)$/i', $prepTime, $regs)) {
                $recipe->preptimeISO = '';
                $recipe->preptime = '';
                $hh = (int)$regs[1];
                $mm = (int)$regs[2];
                if ($hh != 0) {
                    $recipe->preptimeISO .= $hh . 'H';
                    $recipe->preptime = $hh;
                    $recipe->preptime .= $hh == 1 ? 'hour' : 'hours';
                }
                if ($mm != 0) {
                    $recipe->preptimeISO .= $mm . 'M';
                    $recipe->preptime .= $recipe->preptime == '' ? " $mm" : $mm;
                    $recipe->preptime .= $mm == 1 ? 'min' : 'mins';
                }
                if ($recipe->preptime == '') {
                    unset($recipe->preptime);
                    unset($recipe->preptimeISO);
                }
            }
        }

        $totalTime = $recipeElement->getElementsByTagName('TTim');
        if ($totalTime->length > 0) {
            /** @noinspection PhpUndefinedMethodInspection */
            $totalTime = trim($totalTime->item(0)->getAttribute('elapsed'));
            if (preg_match('/^(\d+):(\d+)$/i', $totalTime, $regs)) {
                $recipe->durationISO = '';
                $recipe->duration = '';
                $hh = (int)$regs[1];
                $mm = (int)$regs[2];
                if ($hh != 0) {
                    $recipe->durationISO .= $hh . 'H';
                    $recipe->duration = $hh;
                    $recipe->duration .= $hh == 1 ? 'hour' : 'hours';
                }
                if ($mm != 0) {
                    $recipe->durationISO .= $mm . 'M';
                    $recipe->duration .= $recipe->duration == '' ? " $mm" : $mm;
                    $recipe->duration .= $mm == 1 ? 'min' : 'mins';
                }
                if ($recipe->duration == '') {
                    unset($recipe->duration);
                    unset($recipe->durationISO);
                }
            }
        }

        $ingredients = $recipeElement->getElementsByTagName('IngR');
        $recipe->INGREDIENTS = array();
        $recipe->STEPS = array();
        foreach ($ingredients as $ingredient) {
            /** @var $ingredient DOMElement */
            $prep = $ingredient->getElementsByTagName('IPrp');
            if ($prep->length > 0) {
                $prep = trim($prep->item(0)->nodeValue);
                if ($prep != '') {
                    $prep = " ($prep)";
                }
            } else {
                $prep = '';
            }
            $iLine = trim(trim($ingredient->getAttribute('qty')) . ' ' . trim($ingredient->getAttribute('unit')) . ' ' . trim($ingredient->getAttribute('name')) . $prep);
            if ($iLine != '') {
                $item = new stdClass();
                $code = $ingredient->getAttribute('code');
                if ($code == 'S' || $code == 'T') {
                    $item->hasHeading = true;
                }

                $item->ingredient = $iLine;
                $recipe->INGREDIENTS[] = $item;
            }
        }

        $instructions = $recipeElement->getElementsByTagName('DirT');
        $recipeInstructions = array();
        foreach ($instructions as $instruction) {
            $iLine = preg_replace('/[\r\n]+/', "\n", trim($instruction->nodeValue));
            $iLines = explode("\n", $iLine);
            foreach ($iLines as $iLine) {
                if (trim($iLine) != '') {
                    $item = new stdClass();
                    $item->instruction = $iLine;
                    $recipeInstructions[] = $item;
                }
            }
        }

        $recipe->STEPS[] = new stdClass();
        $recipe->STEPS[0]->INSTRUCTIONS = $recipeInstructions;

        $nutrition = $recipeElement->getElementsByTagName('Nutr');
        if ($nutrition->length > 0) {
            $nutrition = trim($nutrition->item(0)->nodeValue);
            if (preg_match('/^Per Serving [^:]+:(.*) Exchanges:/i', $nutrition, $regs)) {
                $nutrition = $regs[1];
            }
            preg_match_all('/((?:\d+m?g?)) (Calories|Fat|Protein|Carbohydrate|Dietary Fiber|Cholesterol|Sodium)/i', $nutrition, $result, PREG_SET_ORDER);
            for ($i = 0; $i < count($result); $i++) {
                for ($ref = 0; $ref < count($result[$i]); $ref++) {
                    $item = strtolower($result[$i][2]);
                    switch ($item) {
                        case 'dietary fiber' :
                            $item = 'fiber';
                            break;
                        case 'carbohydrate' :
                            $item = 'carbs';
                            break;
                    }

                    $recipe->$item = $result[$i][1];
                }
            }
        }
        return $recipe;
    }

    /**
     * Mastercook can produce concatenated XML files, and they may possibly have different encoding
     * So we need to split the input into separate XML chunks and process each separately
     */
    private function mastercook() {


        $content = @file_get_contents($this->filePath);

        /**
         * Clean up whitespace
         */
        $content = str_replace("\r", "\n", $content);
        $content = preg_replace('/\n\n+/', "\n", $content);
        $content = trim($content);

        $xml = array();
        $nFiles = 1;
        $xml[0] = -1;
        while (($xml[] = strpos($content, '<?xml', $xml[$nFiles - 1] + 1)) !== false) {
            $nFiles++;
        }

        if ($nFiles < 2) {
            $this->result->error = "There are no recipes in this file";
            return;
        }

        /**
         * Get each XML chunk
         */
        $files = array();
        for ($i = 1; $i < $nFiles; $i++) {
            $length = $xml[$i + 1] ? $xml[$i + 1] - $xml[$i] : strlen($content) - $xml[$i];
            $files[] = substr($content, $xml[$i], $length);
        }

        $recipes = array();
        /**
         * Process each xml chunk separately
         */

        while (count($files) > 0) {
            $content = array_pop($files);

            /**
             * Get the encoding and remove the original XML tag
             * Not all mx2 files have an encoding specified - assume UTF-8 if none
             */
            $endXML = strpos($content, '?>') + 2;
            $xmlString = substr($content, 0, $endXML);
            $content = substr($content, $endXML);

            if (preg_match('/(?:encoding="([^"]+)").*/i', $xmlString, $regs)) {
                $encoding = strtoupper($regs[1]);
            } else {
                $encoding = 'UTF-8';
            }

            /**
             * Make a new XML tag that DOMDocument will accept
             * DOMDocument doesn't recognise some attributes that may be present in the original (e.g. standalone)
             */
            $xmlString = "<?xml version=\"1.0\" encoding=\"$encoding\"?>";

            $dom = new EasyRecipePlusDOMDocument("", false);

            if (!@$dom->loadXML($xmlString . $content)) {
                $error = libxml_get_last_error();
                $line = $error ? $error->line : 0;
                $msg = $error ? $error->message : "Unknown error";
                $this->result->error = "Error on line $line: $msg";
                continue;
            }

            $recipeElements = $dom->getElementsByTagName("RcpE");

            if ($recipeElements->length == 0) {
                $this->result->error = "There are no recipes in this file";
                continue;
            }

            foreach ($recipeElements as $recipe) {
                $recipes[] = $this->parseMastercookRecipe($recipe);
            }
            /**
             * Try to salvage some memory - this process is very memory intensive
             */
            $content = null;
            $recipeElements = null;
            $dom = null;
        }
        $this->writeTemp($recipes);
    }

    /**
     * Older style MasterCook format
     *
     * Is essentially a MealMaster format - but slightly different in that the recipe delimeters aren't as well defined
     *
     * There isn't any definitive specification that we can find - so we just have to go on what we've seen
     *
     * A recipe starts with "*  Exported from  MasterCook  *"
     * It seems to end with "- - - - - - - - - - - - - - - - - -"
     */
    private function mastercookmxp() {

        $recipes = array();

        $content = @file_get_contents($this->filePath);

        /**
         * Clean up whitespace
         */
        $content = str_replace("\r\n", "\n", $content);

        /**
         * Nothing but whitespace?
         */
        if (preg_match('/^\s+$/', $content)) {
            $this->result->error = "This seems to be an empty file";
            return;
        }

        /**
         * Do a little pre-processing to make things easier later
         *
         * Some sample files have 0x14 characters in them - remove those
         */
        $content = str_replace(chr(0x14), '', $content);


        /**
         * Strip out rtf stuff
         * VERY basic - we should really have a proper parser - but is it worth it?
         */
        $content = preg_replace('/\{\*?\\\\[^{}]+\}|[{}]|\\\\[A-Za-z]+\n?(?:-?\d+)?[ ]?/i', '', $content);

        $lines = explode("\n", $content);
        $inStatus = self::MM_NONE;

        /**
         * These initialisations aren't necessary but keeps the IntelliJ inspections happy
         */
        $ingredients1 = array();
        $instructionString = '';

        /** @var ImportRecipe $recipe */
        $recipe = new stdClass();
        $recipe->INGREDIENTS = array();
        $recipe->name = '';
        $recipe->yield = '';


        for ($lineIX = 0; $lineIX < count($lines); $lineIX++) {

            $line = rtrim($lines[$lineIX]);

            /**
             * No recipe yet detected - ignore everything until we find a start of recipe
             */
            if ($inStatus == self::MM_NONE) {
                if (!preg_match('/^\s*\*\s+Exported\s+from\s+MasterCook\s+\*/si', $line)) {
                    continue;
                }
                $inStatus = self::MM_STARTED;

                $ingredients1 = array();
                $instructionString = '';
                /** @var ImportRecipe $recipe */
                $recipe = new stdClass();
                $recipe->INGREDIENTS = array();
                continue;
            }

            /**
             * Seen the start of recipe...
             *
             * We expect the next non blank line to be the title
             */

            if ($inStatus == self::MM_STARTED) {
                /**
                 * Ignore blank lines before we get to the headers
                 */
                if ($line == '') {
                    continue;
                }

                $recipe->name = trim($line);
                $inStatus = self::MM_HEADER;
                continue;
            }

            /**
             * Before we get to the ingredients, we can recognise "Recipe by", "Serving size" and "Preparation time"
             * There is also a "Categories" but it's too vague to know what to do with
             *
             * We'll ignore anything other than those fields above until we get to the ingredients
             *
             */
            if ($inStatus == self::MM_HEADER) {
                if ($line == '') {
                    continue;
                }
                if (preg_match('/^Recipe By\s+:\s*(.*)$/si', $line, $regs)) {
                    $recipe->author = $regs[1];
                    continue;
                }
                if (preg_match('/^Serving Size\s+:\s+(.*)Preparation Time :(.*)$/si', $line, $regs)) {
                    $recipe->servingSize = trim($regs[1]);
                    $prepTime = trim($regs[2]);
                    if (preg_match('/^(\d+):(\d+)$/si', $prepTime, $regs)) {
                        $recipe->preptime = $prepTime;
                        $recipe->preptimeISO = $regs[1] . "H" . $regs[2] . "M";
                    }
                    continue;
                }
                /**
                 * Pretty flaky way to recognise the start of ingredients... but it's all we've seen
                 */
                if (strpos($line, '--------  ') !== 0) {
                    continue;
                }
                $inStatus = self::MM_INGREDIENTS;
                continue;
            }
            /**
             * Assume all lines after the header up to the first non-blank are ingredients
             * Unlike MealMaster there seems to be only one ingredient per line
             * Ingredient lines appear to have a max of 70 chars
             * There can be a continuation line, which appears to be defined by '-- ' appearing in col 25 and preceded by spaces, but it's not consistent
             */
            if ($inStatus == self::MM_INGREDIENTS) {
                /**
                 * The first blank line terminates ingredients.
                 * Unlike MealMaster, there are no section headers from what we can tell
                 *
                 * Copy ingredients from (possibly) two cols and also check for continuation lines
                 */
                if ($line == '') {
                    $inStatus = self::MM_INGREDIENTSDONE;
                    foreach ($ingredients1 as $ingredient) {
                        $this->MMIngredient($recipe, $ingredient);
                    }
                    continue;
                }


                /**
                 * Is this a continuation line?
                 * Only check if we already have at least one ingredient since we've seen "continuation" lines as the first ingredient
                 * Continuation lines seem totally inconsistent so we can only go with our best guess
                 */
                if (count($ingredients1) > 0 && strpos($line, '                        -- ') === 0) {
                    $ingredients1[count($ingredients1) - 1] .= substr($line, 27);
                } else {
                    $ingredients1[] = trim($line);
                }

                continue;
            }

            /**
             * At this point, we only have instructions and fluff (calories per serve, "typed by", From:, etc)
             * Instruction formats vary widely as does the fluff
             *
             * Since we can't really be sure how this will appear, we can only really ignore
             * what fluff we recognise and assume everything else is an instruction.
             *
             * It seems to make sense to split instructions into sentences
             *
             * First, check if we got to the end of the ingredients
             *  - this seems to be a line containing only '- - - - - - - - - - - - - - - - - -' and spaces
             *
             * It seems there can be other stuff after the end of the ingredients but we'll just ignore it
             * since there doesn't seem to be any formal fomat
             */
            if (preg_match('/^\s*- - - - - - - - - - - - - - - - - -\s*$/si', $line)) {
                $recipeInstructions = array();
                $steps = explode(". ", $instructionString);
                foreach ($steps as $step) {
                    $step = trim($step);
                    if ($step != '') {
                        $item = new stdClass();
                        $item->instruction = $step;
                        $recipeInstructions[] = $item;
                    }
                }
                $recipe->STEPS[] = new stdClass();
                $recipe->STEPS[0]->INSTRUCTIONS = $recipeInstructions;
                $recipes[] = $recipe;
                $inStatus = self::MM_NONE;
                continue;
            }

            /**
             * Everything from here is assumed to be instructions or fluff
             */
            $line = trim($line);
            if ($line != '') {
                /**
                 * Do we recognise it as fluff where we can't be sure of the format?
                 * Unlike Mealmaster, these can occur in the body of the ingredients
                 */

                if (preg_match("/^(?:$this->fluff)/i", $line)) {
                    continue;
                }

                /**
                 * We (probably) have an instruction line now..
                 * Ignore lines that have no letters - tend to be delimiter lines
                 *
                 * Remove any leading numbering and append it to the instructions string
                 */

                if (preg_match('/[\p{L}]/u', $line)) {
                    $instructionString .= preg_replace('/^\d+\.\s*/', '', $line) . ' ';
                }
            }
        }

        if (count($recipes) > 0) {
            $this->writeTemp($recipes);
        }
    }

    /**
     * @param $match
     *
     * @return string
     */
    private function replaceBRs($match) {
        return '<div itemprop="instructions">' . str_ireplace("<br>", "\n", $match[1]) . '</div>';
    }

    /**
     *
     */
    private function cookn() {
        if (!class_exists('EasyRecipePlusMicrodata', false)) {
            require_once 'EasyRecipePlusMicrodata.php';
        }

        $lines = @file_get_contents($this->filePath);
        if ($lines === false || $lines == '') {
            $this->result->error = "This seems to be an empty file"; // TODO - display filename
            return;
        }

        // $lines = utf8_encode($lines);

        $recipes = array();
        /*
         * Convert <br>'s in the ingredients to a single LF
         * Clean up the whitespace so we don't have to fuss with it later
         */
        $lines = preg_replace_callback('%<div\s+itemprop="instructions">(.*?)</div>%si', array($this, 'replaceBRs'), $lines);
        $lines = preg_replace('/[\r\n]+/', "\n", $lines);
        $lines = preg_replace('/\t+/', ' ', $lines);

        $dom = new EasyRecipePlusMicrodata($lines);

        if (!$dom->isValid()) {
            $error = libxml_get_last_error();
            $line = $error ? $error->line : 0;
            $msg = $error ? $error->message : "Unknown error";
            $this->result->error = "Error on line $line: $msg";
            return;
        }

        $microdata = $dom->getItems('http://data-vocabulary.org/Recipe');
        $cooknRecipes = $microdata->items;

        $value = "";
        foreach ($cooknRecipes as $cooknRecipe) {

            /** @var ImportRecipe $recipe */
            $recipe = new stdClass();
            $recipe->STEPS = array();
            $recipeInstructions = array();
            foreach ($cooknRecipe->properties as $key => $values) {
                if (is_string($values[0])) {
                    $value = trim($values[0]);
                    if ($value == '') {
                        continue;
                    }
                }
                switch ($key) {
                    case 'name' :
                        $recipe->name = $value;
                        break;

                    case 'summary' :
                        $recipe->summary = $value;
                        break;

                    case 'prepTime' :
                        $recipe->preptime = $value;
                        break;

                    case 'cookTime' :
                        $recipe->cooktime = $value;
                        break;

                    case 'yield' :
                        $recipe->yield = $value;
                        break;

                    case 'nutrition' :
                        $nutrition = $values[0];
                        /** @noinspection PhpUndefinedFieldInspection */
                        foreach ($nutrition->properties as $nKey => $nValue) {
                            switch ($nKey) {
                                case 'servingSize' :
                                    break;
                                case 'calories' :
                                    break;
                            }
                        }
                        break;

                    case 'ingredient' :
                        foreach ($values as $ingredient) {
                            $iLine = trim($ingredient->nodeValue);
                            $iLine = preg_replace('/\s+/si', ' ', $iLine);
                            if ($iLine != '') {
                                $item = new stdClass();
                                $item->ingredient = $iLine;
                                $recipe->INGREDIENTS[] = $item;
                            }
                        }
                        break;

                    case 'instructions' :
                        $iLines = explode("\n", trim($value));
                        foreach ($iLines as $iLine) {
                            if ($iLine != '') {
                                $item = new stdClass();
                                $item->instruction = $iLine;
                                $recipeInstructions[] = $item;
                            }
                        }
                        $recipe->STEPS[] = new stdClass();
                        $recipe->STEPS[0]->INSTRUCTIONS = $recipeInstructions;

                        break;
                }
            }
            $recipes[] = $recipe;
        }
        $this->writeTemp($recipes);
    }

    /**
     * Cookbook Wiz
     */
    private function cookbookwiz() {
        $lines = @file($this->filePath);
        if (!$lines || count($lines) == 0) {
            $this->result->error = "This seems to be an empty file";
            return;
        }

        $isIngredients = false;
        $isInstructions = false;
        $recipeInstructions = array();

        $recipes = array();
        $recipe = null;
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line == '') {
                continue;
            }

            if ($line == '**Exported from Cookbook Wizard Recipe Software**v2.0') {
                if ($recipe) {
                    $recipes[] = $recipe;
                }

                /** @var ImportRecipe $recipe */
                $recipe = new stdClass();
                $recipe->INGREDIENTS = array();
                $recipe->STEPS = array();
                $recipeInstructions = array();
                $isIngredients = false;
                $isInstructions = false;
                continue;
            }

            if ($line == '-------------v2.0') {
                $recipe->STEPS[] = new stdClass();
                $recipe->STEPS[0]->INSTRUCTIONS = $recipeInstructions;

                $recipes[] = $recipe;
                $recipe = null;
                continue;
            }

            if ($recipe == null) {
                $this->result->error = "Invalid recipe format";
                return;
            }
            $line = utf8_encode($line);
            if (preg_match('/^(Recipe Name|Cuisine|Category|Servings|Ingredients|Instructions):\s*(.*)$/i', $line, $regs)) {
                $value = trim($regs[2]);
                switch ($regs[1]) {
                    case 'Recipe Name' :
                        if ($value != '') {
                            $recipe->name = $value;;
                        }
                        continue 2;

                    case 'Cuisine' :
                        if ($value != '') {
                            $recipe->cuisine = $value;
                        }
                        continue 2;

                    case 'Category' :
                        if ($value != '') {
                            $recipe->category = $value;
                        }
                        continue 2;

                    case 'Servings' :
                        if ($value != '') {
                            $recipe->yield = $value;
                        }
                        continue 2;

                    case 'Ingredients' :
                        $isIngredients = true;
                        continue 2;

                    case 'Instructions' :
                        $isIngredients = false;
                        $isInstructions = true;
                        continue 2;

                    default :
                        if (!$isIngredients && !$isInstructions) {
                            continue 2;
                        }
                        break;
                }
            }

            if ($isIngredients) {
                $item = new stdClass();

                if (preg_match('/^\s*([^:]+):\s*$/i', $line, $regs)) {
                    $item->hasHeading = true;
                    $item->ingredient = $regs[1];
                } else if (preg_match('/^\s*For the ([^:]+):?$/i', $line, $regs)) {
                    $item->hasHeading = true;
                    $item->ingredient = $regs[1];
                } else {
                    $item->ingredient = trim($line, " *");
                }
                $recipe->INGREDIENTS[] = $item;

                continue;
            }

            if ($isInstructions) {
                $item = new stdClass();
                $item->instruction = preg_replace('/^(?:(?:\d+\.\s+)|(?:\.+\s*)|(?:\*+ *))(.*)$/i', '$1', $line);
                $recipeInstructions[] = $item;
                continue;
            }
        }

        if ($recipe) {
            $recipes[] = $recipe;
        }
        $this->writeTemp($recipes);
    }

    /**
     * Yummy Soup files seem to be a well formed Property List (yay!) so we should be able to parse it reliably although we've only seen a few examples
     * http://www.apple.com/DTDs/PropertyList-1.0.dtd
     */

    private function yummysoup() {
        $content = @file_get_contents($this->filePath);

        /**
         * Clean up whitespace
         */
        $content = str_replace("\t", "", $content);
        $content = str_replace("\r", "\n", $content);
        $content = preg_replace('/\n\n+/', "\n", $content);
        $content = trim($content);

        $parser = new EasyRecipePlusPlistParser();

        $recipes = array();
        $plistArray = (array)$parser->parse($content);

        /** @var $plistRecipe EasyRecipePlusPlistDict */
        foreach ($plistArray as $plistRecipe) {

            /** @var ImportRecipe $recipe */
            $recipe = new stdClass();
            $recipe->INGREDIENTS = array();
            $recipe->STEPS = array();

            $recipe->name = $plistRecipe->getString('name');
            $recipe->cuisine = $plistRecipe->getString('cuisine');
            $recipe->summary = $plistRecipe->getString('recipeDescription');
            $recipe->yield = $plistRecipe->getString('yield');
            $recipe->notes = $plistRecipe->getString('notes');
            /**
             * Strip HTML tags and line numbering from directions
             */
            $directions = explode("\n", $plistRecipe->getString('directions'));
            $instructions = array();

            foreach ($directions as $direction) {
                $item = new stdClass();
                $direction = strip_tags($direction);
                $direction = trim(preg_replace('/^\d+\.\p{Zs}+(.*)$/u', '$1', $direction));
                if ($direction != '') {
                    $item->instruction = $direction;
                    $instructions[] = $item;
                }
            }
            $recipe->STEPS[] = new stdClass();
            $recipe->STEPS[0]->INSTRUCTIONS = $instructions;

            /**
             * Ingredients are in some sort of serialized form
             * The following is a very naive unserialize based on what little data we've seen so far
             * We'll assume that there are no errors in the serialized data
             */
            $ingredientLines = explode("\n", $plistRecipe->getString('ingredientsArray'));

            for ($i = 0; $i < count($ingredientLines); $i++) {
                $line = trim($ingredientLines[$i]);
                /**
                 * I would guess (..) indicates an array element, but we've only ever seen a single element in the ingredients list so ignore it for now
                 */
                if ($line == '(' || $line == ')') {
                    continue;
                }
                /**
                 * A "{" indicates the start of a single ingredient object
                 */
                if ($line == '{') {
                    $ingredient = new stdClass();
                    do {
                        $i++;
                        $line = trim($ingredientLines[$i]);
                        $line = rtrim($line, ",");
                        if (preg_match('/^(measurement|method|name|quantity)\s*=\s*"?(.*?)"?;$/si', $line, $regs)) {
                            $ingredient->{$regs[1]} = $regs[2];
                        }
                    } while ($line != '}');
                    $item = new stdClass();
                    $quantity = isset($ingredient->quantity) ? $ingredient->quantity : '';
                    $measurement = isset($ingredient->measurement) ? $ingredient->measurement : '';
                    $name = isset($ingredient->name) ? $ingredient->name : '';
                    $method = isset($ingredient->method) ? $ingredient->method : '';
                    $item->ingredient = "$quantity $measurement $name";
                    if ($method != '') {
                        $item->ingredient .= " ($method)";
                    }
                    $item->ingredient = str_replace("  ", " ", $item->ingredient);
                    $recipe->INGREDIENTS[] = $item;
                }
            }
            $recipes[] = $recipe;
        }

        if (count($recipes) > 0) {
            $this->writeTemp($recipes);
        }

    }

    /**
     * MacGourmet files seem to be a well formed Property List (yay!) so we should be able to parse it reliably
     * http://www.apple.com/DTDs/PropertyList-1.0.dtd
     */
    private function macgourmet() {
        $content = @file_get_contents($this->filePath);

        /**
         * Clean up whitespace
         */
        $content = str_replace("\t", "", $content);
        $content = str_replace("\r", "\n", $content);
        $content = preg_replace('/\n\n+/', "\n", $content);
        $content = trim($content);

        $parser = new EasyRecipePlusPlistParser();


        $plistArray = (array)$parser->parse($content);

        $recipes = array();


        /** @var $plistRecipe EasyRecipePlusPlistDict */
        foreach ($plistArray as $plistRecipe) {

            /** @var ImportRecipe $recipe */
            $recipe = new stdClass();

            $recipe->INGREDIENTS = array();
            $recipe->STEPS = array();

            $recipe->version = $this->version;
            $recipe->type = $plistRecipe->getString('COURSE_NAME');

            $recipe->name = $plistRecipe->getString('NAME');
            $recipe->summary = $plistRecipe->getString('SUMMARY');
            $recipe->yield = $plistRecipe->getString('YIELD');
            $preptimes = $plistRecipe->getArray('PREP_TIMES');
            $cuisines = $plistRecipe->getArray('CUISINE');


            /**
             * We can only handle one cuisine for now
             */
            $recipe->cuisine = count($cuisines) > 0 ? $cuisines[0]['NAME'] : '';

            $directions = $plistRecipe->getArray('DIRECTIONS_LIST');
            $ingredients = $plistRecipe->getArray('INGREDIENTS_TREE');

            foreach ($ingredients as $ingredientDetails) {
                $item = new stdClass();
                $item->ingredient = sprintf("%s %s %s", $ingredientDetails['QUANTITY'], $ingredientDetails['MEASUREMENT'], $ingredientDetails['DESCRIPTION']);
                if ($ingredientDetails['DIRECTION'] != '') {
                    $item->ingredient .= " (" . $ingredientDetails['DIRECTION'] . ")";
                }

                if ($ingredientDetails['IS_DIVIDER']) {
                    $item->hasHeading = true;
                }
                $recipe->INGREDIENTS[] = $item;
            }

            $instructions = array();
            foreach ($directions as $direction) {
                $item = new stdClass();
                $item->instruction = $direction['DIRECTION_TEXT'];
                $instructions[] = $item;
            }
            $recipe->STEPS[] = new stdClass();
            $recipe->STEPS[0]->INSTRUCTIONS = $instructions;

            $recipes[] = $recipe;
        }

        if (count($recipes) > 0) {
            $this->writeTemp($recipes);
        }


    }

    /**
     * .paprika files are json encoded and gzipped
     *
     * .paprikas files are gzipped collections of .paprika files
     *
     * @param bool $isMulti
     */
    private function paprika($isMulti = false) {
        $recipes = array();

        if (!$isMulti) {
            /** @var ImportRecipe $recipe */
            $recipe = $this->parsePaprika($this->filePath);
            if ($recipe) {
                $recipes[] = $recipe;
            }
        } else {
            $za = new ZipArchive();

            $result = $za->open($this->filePath);
            if ($result !== true) {
                $this->result->error = "Error ($result) opening .paprikas file";
                return;
            }
            $tmpName = @tempnam('', 'ERI');
            for ($i = 0; $i < $za->numFiles; $i++) {
                $name = $za->getNameIndex($i);
                if (!$name) {
                    $this->result->error = "Error getting file name from .paprikas archive";
                    @unlink($tmpName);
                    return;
                }

                $fp = $za->getStream($name);

                $content = '';
                while (!feof($fp)) {
                    $content .= fread($fp, 8192);
                }

                $tmp = @fopen($tmpName, "w");
                if (!$tmp) {
                    $this->result->error = "Can't open $tmpName for writing";
                    return;
                }

                @fputs($tmp, $content);
                @fclose($fp);

                $recipe = $this->parsePaprika($tmpName);
                if ($recipe) {
                    $recipes[] = $recipe;
                }
            }

            @unlink($tmpName);
        }
        if (count($recipes) > 0) {
            $this->writeTemp($recipes);
        }
    }


    /**
     *  Parse a single Paprika recipe
     *
     * @param $inFile
     * @return null|ImportRecipe
     */
    private function parsePaprika($inFile) {

        $fp = @fopen($inFile, "rb");
        if (!$fp) {
            $this->result->error = "Error opening $inFile";
            return null;
        }

        /**
         * Find the size of the zipfile
         */
        @fseek($fp, -4, SEEK_END);
        $buf = @fread($fp, 4);
        $offset = unpack("V", $buf);
        $fSize = end($offset);
        @fclose($fp);

        $gz = @gzopen($inFile, "r");
        if (!$gz) {
            $this->result->error = "Error opening input file";
            return null;
        }

        $contents = @gzread($gz, $fSize);
        @gzclose($gz);

        $paprika = @json_decode($contents);
        if (!$paprika) {
            $this->result->error = "Error decoding data";
            return null;
        }

        $recipeInstructions = array();
        $recipe = new stdClass();
        $recipe->INGREDIENTS = array();

        $recipe->name = $paprika->name;
        if ($paprika->servings != '') {
            $recipe->yield = $paprika->servings;
        }
        if ($paprika->notes != '') {
            $recipe->notes = $paprika->notes;
        }
        $directions = explode("\n", $paprika->directions);
        $ingredients = explode("\n", $paprika->ingredients);

        foreach ($directions as $instruction) {
            $instruction = trim($instruction);
            if ($instruction == '') {
                continue;
            }
            $item = new stdClass();
            /**
             * Remove any numbering
             */
            $item->instruction = preg_replace('/^\d+\.\s*/', '', $instruction) . ' ';
            $recipeInstructions[] = $item;
        }

        foreach ($ingredients as $ingredient) {
            $ingredient = trim($ingredient);
            if ($ingredient == '') {
                continue;
            }
            $item = new stdClass();
            /**
             * Is this a section head?
             */
            $head = rtrim($ingredient, ':');
            if ($head != $ingredient) {
                $item->hasHeading = true;
                $item->ingredient = $ingredient;
            } else {
                $item->ingredient = $ingredient;
            }
            $recipe->INGREDIENTS[] = $item;

        }
        $recipe->STEPS[] = new stdClass();
        $recipe->STEPS[0]->INSTRUCTIONS = $recipeInstructions;
        return $recipe;
    }

    /**
     * Meal-Master
     *
     * Multiple recipes per file, no explicit encoding, plain text
     *
     * There seem to be a heap of variations in .mmf file formats and none
     * are well specified so there's a fair bit of guesswork here
     */
    private function mealmaster() {


        $recipes = array();

        $content = @file_get_contents($this->filePath);

        /**
         * Clean up whitespace
         */
        $content = str_replace("\r\n", "\n", $content);

        /**
         * Nothing but whitespace?
         */
        if (preg_match('/^\s+$/', $content)) {
            $this->result->error = "This seems to be an empty file";
            return;
        }

        /**
         * Do a little pre-processing to make things easier later
         *
         * Some sample files have 0x14 characters in them - remove those
         */
        $content = str_replace(chr(0x14), '', $content);

        /**
         * Normalise some non-standard section head conventions
         */
        $content = preg_replace('/\n\s+1 x\s+----+([^-]+)----+\s*\n/s', '--------------------------------$1--------------------------------' . "\n", $content);

        $lines = explode("\n", $content);
        $inStatus = self::MM_NONE;

        /**
         * These initialisations aren't necessary but keeps the IntelliJ inspections happy
         */
        $ingredients1 = array();
        $ingredients2 = array();
        $endRegex = '';
        $instructionString = '';
        $recipe = new stdClass();
        $recipe->INGREDIENTS = array();
        $recipe->name = '';
        $recipe->yield = '';


        for ($lineIX = 0; $lineIX < count($lines); $lineIX++) {

            $line = rtrim($lines[$lineIX]);

            /**
             * No recipe yet detected - ignore everything until we find a start of recipe
             */
            if ($inStatus == self::MM_NONE) {
                if (!preg_match('/^([M-]{5}).*Meal-Master/', $line, $regs)) {
                    continue;
                }
                /**
                 * Keep track of the terminator character
                 */
                $endRegex = sprintf('/^%s{5}%s*$/', $regs[1][0], $regs[1][0]);
                $inStatus = self::MM_STARTED;
                $headers = array();
                $ingredients1 = array();
                $ingredients2 = array();
                $instructionString = '';
                $recipe = new stdClass();
                $recipe->INGREDIENTS = array();
                continue;
            }

            /**
             * Seen the start of recipe...
             * We expect Title:, Categories:, Yield:
             */

            if ($inStatus == self::MM_STARTED || $inStatus == self::MM_HEADER) {
                /**
                 * Ignore blank lines before we get to the headers
                 */
                if ($inStatus == self::MM_STARTED && $line == '') {
                    continue;
                }

                /**
                 * Do we recognise a header?
                 */
                if (preg_match('/^\s*(Title|Categories|Yield):\s+(.*)$/', $line, $regs)) {
                    $inStatus = self::MM_HEADER;
                    if ($regs[1] == 'Title') {
                        $recipe->name = $regs[2];
                    } else if ($regs[1] == 'Yield') {
                        $recipe->yield = $regs[2];
                    }
                    $headers[$regs[1]] = $regs[2];
                    continue;
                }
                /**
                 * A blank line finishes the header - assume everything past here until the next blank line are ingredients
                 */
                if ($line == '') {
                    $inStatus = self::MM_INGREDIENTS;
                }
                continue;
            }

            /**
             * Assume all lines after the header up to the first non-blank are ingredients
             * Ingredients occupy a maximum of 40 chars, however there may be two ingredients per line
             * (second ingredient seems to start in col 42)
             * Process the all the first column ingredients before the second
             */
            if ($inStatus == self::MM_INGREDIENTS) {
                /**
                 * The first blank line terminates ingredients, UNLESS the next line is a section header
                 *
                 * Copy ingredients from (possibly) two cols and also check for continuation lines
                 */
                if ($line == '') {
                    $isHeader = false;

                    if ($lineIX != count($lines) - 1) {
                        $isHeader = preg_match('/^(?:MMMMM)?----+[^-]+----+$/i', $lines[$lineIX + 1]);
                    }

                    if (!$isHeader) {
                        $inStatus = self::MM_INGREDIENTSDONE;
                        foreach ($ingredients1 as $ingredient) {
                            $this->MMIngredient($recipe, $ingredient);
                        }
                        foreach ($ingredients2 as $ingredient) {
                            $this->MMIngredient($recipe, $ingredient);
                        }
                    }
                    continue;
                }


                /**
                 * Is this a long line?  It could be a section head or it could be two on a line
                 * More than 1 per line?  Save the second on the line for later
                 */
                if (strlen($line) < 40) {
                    $ingredients1[] = trim($line);
                } else {
                    /**
                     * Section heading or two up?
                     */
                    if (preg_match('/^(?:MMMMM)?-+([^-]+)-+$/', $line, $regs)) {
                        $ingredients1[] = "!" . $regs[1];
                    } else {
                        $ingredients1[] = trim(substr($line, 0, 40));
                        $ingredients2[] = trim(substr($line, 42));
                    }
                }
                continue;
            }

            /**
             * At this point, we only have instructions and fluff (calories per serve, "typed by", From:, etc)
             * Instruction formats vary widely as does the fluff
             *
             * Since we can't really be sure how this will appear, we can only really ignore
             * what fluff we recognise and assume everything else is an instruction.
             *
             * It seems to make sense to split instructions into sentences
             *
             * First, check if we got to the end of the recipe - this is (at least) 5 repeated start chars
             */
            if (preg_match($endRegex, $line)) {
                $recipeInstructions = array();
                $steps = explode(". ", $instructionString);
                foreach ($steps as $step) {
                    $step = trim($step);
                    if ($step != '') {
                        $item = new stdClass();
                        $item->instruction = $step;
                        $recipeInstructions[] = $item;
                    }
                }
                $recipe->STEPS[] = new stdClass();
                $recipe->STEPS[0]->INSTRUCTIONS = $recipeInstructions;
                $recipes[] = $recipe;
                $inStatus = self::MM_NONE;
                continue;
            }

            /**
             * If we previously found some ending fluff, ignore everything until the end of recipe
             */
            if ($inStatus == self::MM_FINISHED) {
                continue;
            }
            $line = trim($line);
            if ($line != '') {
                /**
                 * Do we recognise it as fluff where we can't be sure of the format?
                 * These seem to only occur at the end of a recipe so ignore everything until the end once we spot one
                 */

                if (preg_match("/^(?:$this->fluff)/i", $line)) {
                    $inStatus = self::MM_FINISHED;
                    continue;
                }

                /**
                 * We (probably) have an instruction line now..
                 * Ignore lines that have no letters - tend to be delimiter lines
                 *
                 * Remove any leading numbering and append it to the instructions string
                 */

                if (preg_match('/[\p{L}]/u', $line)) {
                    $instructionString .= preg_replace('/^\d+\.\s*/', '', $line) . ' ';
                }
            }
        }

        if (count($recipes) > 0) {
            $this->writeTemp($recipes);
        }
    }

    /**
     * Process the ingredients arrays for meal-master and MAstewrCook .mxp
     *
     * @param $recipe
     * @param $ingredient
     */
    private function MMIngredient($recipe, $ingredient) {
        /**
         * Is this a continuation?
         */
        if (preg_match('/^-+ *(.*)$/', $ingredient, $regs) && count($recipe->INGREDIENTS) > 0) {
            $item = $recipe->INGREDIENTS[count($recipe->INGREDIENTS) - 1];
            $item->ingredient .= ' ' . $regs[1];
        } else {
            $item = new stdClass();
            if ($ingredient[0] == '!') {
                $item->hasHeading = true;
                $item->ingredient = substr($ingredient, 1);
            } else {
                $item->ingredient = $ingredient;
            }
            $recipe->INGREDIENTS[] = $item;
        }

    }

}

