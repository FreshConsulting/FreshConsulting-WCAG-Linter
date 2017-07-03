<?php

class FreshConsulting_Sniffs_WCAG20_ViolationsSniff implements PHP_CodeSniffer_Sniff
{

    /**
     * Returns the token types that this sniff is interested in.
     *
     * @return array(int)
     */
    public function register() {
        // these tokens may contain HTML
        return array_map('constant', $this->register_strings());
        // consider T_DOUBLE_QUOTED_STRING? PHP variables may add attributes though

    }


    private function register_strings() {
        // PHP token constants as strings
        return ['T_CONSTANT_ENCAPSED_STRING', 'T_HEREDOC', 'T_INLINE_HTML'];
    }


    // regular expressions may appear to contain html
    private function is_regex_function_name($function_name) {
        return in_array($function_name, ['preg_filter', 'preg_grep', 'preg_match_all', 'preg_match', 'preg_replace_callback', 'preg_replace', 'preg_split']);
    }


    private function is_missing_form_label($domelement, $label_for_ids) {
        if ($domelement->hasAttribute('type') && in_array($domelement->getAttribute('type'), array('image', 'submit', 'reset', 'button', 'hidden'))) {
            return false;
        }
        if ($domelement->hasAttribute('id') && in_array($domelement->getAttribute('id'), $label_for_ids)) {
            return false;
        }
        if ($domelement->hasAttribute('title') || $domelement->hasAttribute('aria-label')) {
            return false;
        }
        return true;
    }


    private function is_empty_button_input($domelement) {
        if ($domelement->hasAttribute('type') && in_array($domelement->getAttribute('type'), array('submit', 'button', 'reset'))) {
            if ($domelement->hasAttribute('aria-label')) {
                return false;
            }
            if ($domelement->hasAttribute('value') && '' !== trim($domelement->getAttribute('value'))) {
                return false;
            }
            return true;
        }
        return false;
    }


    /**
     * Processes the tokens that this sniff is interested in.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file where the token was found.
     * @param int                  $stackPtr  The position in the stack where
     *                                        the token was found.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr) {
        $tokens = $phpcsFile->getTokens();

        // if we're a regex argument to a preg_* function then continue
        // e.g. skip:
        //   if ( preg_match_all( '/<img [^>]+>/', $content, $matches ) && current_user_can( 'upload_files' ) ) {
        //
        // https://pear.php.net/package/PHP_CodeSniffer/docs/latest/apidoc/PHP_CodeSniffer/File.html#methodfindPrevious
        $prev_token_stackPtr = $phpcsFile->findPrevious( array( T_WHITESPACE ), $stackPtr - 1, null, true, null, true );
        if ('T_OPEN_PARENTHESIS' === $tokens[$prev_token_stackPtr]['type']) {
            $prev_prev_token_stackPtr = $phpcsFile->findPrevious( array( T_WHITESPACE, T_OPEN_PARENTHESIS ), $stackPtr - 1, null, true, null, true );
            if ($this->is_regex_function_name($tokens[$prev_prev_token_stackPtr]['content'])) {
                return;
            }
        }

        // found some text--it might be HTML!
        // line breaks always create new tokens
        // look ahead to the next token--is it also text?
        if (count($tokens) > $stackPtr + 1 && in_array($tokens[$stackPtr + 1]['type'], $this->register_strings())) {
            // we'll pick this token up when we process the next token
            return;
        }
        // else: next token is not text so let's process this and all immediately previous text tokens

        // grab previous tokens to create largest possible HTML block
        $combined_content = '';
        $text_stackPtr = $stackPtr;
        while ($text_stackPtr >= 0 && in_array($tokens[$text_stackPtr]['type'], $this->register_strings())) {
            // TODO: strip quotes from T_CONSTANT_ENCAPSED_STRING strings (which may be split across multiple lines)
            $combined_content = $tokens[$text_stackPtr]['content'] . $combined_content;
            $text_stackPtr--;
        }

        $content = trim($combined_content);
        if ('' === $content) {
            // nothing to see here
            return;
        }

        // the HTML parser will complete non-closed elements
        // if the element is not properly closed we cannot judge its validity
        // this hack will cause non-closed <img> elements to always appear valid
        // and non-closed <a> elements to have nodeValue text
        // this additional text will be ignored by properly closed elements
        $content = $content . ' \'" alt="foo" >';

        // try to parse the text as HTML
        libxml_use_internal_errors(true);
        $dom = new DOMDocument;
        $dom->loadHTML($content);
        libxml_clear_errors();

        // save label for ids for later
        $label_for_ids = array();
        foreach ($dom->getElementsByTagName('label') as $label) {
            if ($label->hasAttribute('for')) {
                $label_for_ids[] = $label->getAttribute('for');
            }
        }

        foreach ($dom->getElementsByTagName('input') as $form_control) {
            if ($this->is_missing_form_label($form_control, $label_for_ids)) {
                // from WAVE tool (http://wave.webaim.org/)
                // Errors:
                // Missing form label
                // What It Means:
                // A form control does not have a corresponding label.
                // Why It Matters:
                // If a form control does not have a properly associated text label, the function or purpose of that form control may not be presented to screen reader users. Form labels also provide visible descriptions and larger clickable targets for form controls.
                // How to Fix It:
                // If a text label for a form control is visible, use the <label> element to associate it with its respective form control. If there is no visible label, either provide an associated label, add a descriptive title attribute to the form control, or reference the label(s) using aria-labelledby. Labels are not required for image, submit, reset, button, or hidden form controls.
                // The Algorithm... in English
                // An <input> (except types of image, submit, reset, button, or hidden), <select>, or <textarea> does not have a properly associated label text. A properly associated label is:
                // a <label> element with a for attribute value that is equal to the id of a unique form control
                // a <label> element that surrounds the form control, does not surround any other form controls, and does not reference another element with its for attribute
                // a non-empty title attribute, or
                // a non-empty aria-labelledby attribute.
                $phpcsFile->addError('All visible <input> tags must have a title or aria-label attribute or associated <label>', $stackPtr, 'Missing form label');
            }

            if ($this->is_empty_button_input($form_control)) {
                // from WAVE tool (http://wave.webaim.org/)
                // Errors:
                // Empty button
                // What It Means:
                // A button is empty or has no value text.
                // Why It Matters:
                // When navigating to a button, descriptive text must be presented to screen reader users to indicate the function of the button.
                // How to Fix It:
                // Place text content within the <button> element or give the <input> element a value attribute.
                // The Algorithm... in English:
                // A <button> element is present that contains no text content (or alternative text), or an <input type="submit">, <input type="button">, or <input type="reset"> has an empty or missing value attribute.
                $phpcsFile->addError('All <input>tags with type "submit", "button", or "reset" must have an aria-label or non-empty value attribute', $stackPtr, 'Empty Button');
            }
        }
        foreach ($dom->getElementsByTagName('select') as $form_control) {
            if ($this->is_missing_form_label($form_control, $label_for_ids)) {
                $phpcsFile->addError('All visible <select> tags must have a title or aria-label attribute or associated <label>', $stackPtr, 'Missing form label');
            }
        }
        foreach ($dom->getElementsByTagName('textarea') as $form_control) {
            if ($this->is_missing_form_label($form_control, $label_for_ids)) {
                $phpcsFile->addError('All visible <textarea> tags must have a title or aria-label attribute or associated <label>', $stackPtr, 'Missing form label');
            }
        }

        foreach ($dom->getElementsByTagName('img') as $image) {
            if (!$image->hasAttribute('alt')) {
                // from WAVE tool (http://wave.webaim.org/)
                // Errors:
                // Missing alternative text
                // What It Means:
                // Image alternative text is not present.
                // Why It Matters:
                // Each image must have an alt attribute. Without alternative text, the content of an image will not be available to screen reader users or when the image is unavailable.
                // How to Fix It:
                // Add an alt attribute to the image. The attribute value should accurately and succinctly present the content and function of the image. If the content of the image is conveyed in the context or surroundings of the image, or if the image does not convey content or have a function, it should be given empty/null alternative text (alt="").
                // The Algorithm... in English:
                // An image does not have an alt attribute.
                $phpcsFile->addError('All <img> tags must have an alt attribute', $stackPtr, 'Missing alternative text');
            }
        }

        foreach ($dom->getElementsByTagName('a') as $link) {
            foreach ($link->getElementsByTagName('img') as $link_image) {
                if ('' === trim($link_image->getAttribute('alt'))) {
                    // from WAVE tool (http://wave.webaim.org/)
                    // Errors:
                    // Linked image missing alternative text
                    // What It Means:
                    // An image without alternative text results in an empty link.
                    // Why It Matters:
                    // Images that are the only thing within a link must have descriptive alternative text. If an image is within a link that contains no text and that image does not provide alternative text, a screen reader has no content to present to the user regarding the function of the link.
                    // How to Fix It:
                    // Add appropriate alternative text that presents the content of the image and/or the function of the link.
                    // The Algorithm... in English:
                    // An image without alternative text (missing alt attribute or an alt value that is null/empty or only space characters) is within a link that contains no text and no images with alternative text.
                    $phpcsFile->addError('All <img> tags in <a> tags must have a non-empty alt attribute', $stackPtr, 'Linked image missing alternative text');
                }
            }

        }

        // DomDocument strips closing </a> tags so we can't differentiate 
        // {<a href=""></a>} from {<a href="">}<?php echo $foo; ?\></a>
        // fall back to a regex...
        unset($matches);
        $keep_going = true;
        $offset = 0;
        while ($keep_going) {
            if (1 === preg_match('/<a ([^>]*)>\s*<\/a>/', $content, $matches, PREG_OFFSET_CAPTURE, $offset)) {
                if (isset($matches[1]) && false !== stristr($matches[1][0], 'href') && false === stristr($matches[1][0], 'aria-label')) {
                    // from WAVE tool (http://wave.webaim.org/)
                    // Errors:
                    // Empty link
                    // What It Means:
                    // A link contains no text.
                    // Why It Matters:
                    // If a link contains no text, the function or purpose of the link will not be presented to the user. This can introduce confusion for keyboard and screen reader users.
                    // How to Fix It:
                    // Remove the empty link or provide text within the link that describes the functionality and/or target of that link.
                    // The Algorithm... in English:
                    // An anchor element has an href attribute, but contains no text (or only spaces) and no images with alternative text.
                    $phpcsFile->addError('All <a> tags which do not enclose text must have an aria-label attribute (%s)', $stackPtr, 'Empty link', array($matches[0][0]));
                }
                $offset = $matches[0][1] + strlen($matches[0][0]);
            } else {
                $keep_going = false;
            }
        }
        unset($matches);
        $keep_going = true;
        $offset = 0;
        while ($keep_going) {
            if (1 === preg_match('/<a ([^>]*)>\s*<(span|div) [^>]*>\s*<\/\2>\s*<\/a>/', $content, $matches, PREG_OFFSET_CAPTURE, $offset)) {
                if (isset($matches[1]) && false !== stristr($matches[1][0], 'href') && false === stristr($matches[1][0], 'aria-label')) {
                    $phpcsFile->addError('All <a> tags which do not enclose text must have an aria-label attribute (%s)', $stackPtr, 'Empty link', array($matches[0][0]));
                }
                $offset = $matches[0][1] + strlen($matches[0][0]);
            } else {
                $keep_going = false;
            }
        }

        unset($matches);
        $keep_going = true;
        $offset = 0;
        while ($keep_going) {
            if (1 === preg_match('/<button ([^>]*)>\s*<\/button>/', $content, $matches, PREG_OFFSET_CAPTURE, $offset)) {
                if (isset($matches[1]) && false === stristr($matches[1][0], 'aria-label')) {
                    $phpcsFile->addError('All <button> tags which do not enclose text must have an aria-label attribute (%s)', $stackPtr, 'Empty button', array($matches[0][0]));
                }
                $offset = $matches[0][1] + strlen($matches[0][0]);
            } else {
                $keep_going = false;
            }
        }
        unset($matches);
        $keep_going = true;
        $offset = 0;
        while ($keep_going) {
            if (1 === preg_match('/<button ([^>]*)>\s*<(span|div) [^>]*>\s*<\/\2>\s*<\/button>/', $content, $matches, PREG_OFFSET_CAPTURE, $offset)) {
                if (isset($matches[1]) && false === stristr($matches[1][0], 'aria-label')) {
                    $phpcsFile->addError('All <button> tags which do not enclose text must have an aria-label attribute (%s)', $stackPtr, 'Empty button', array($matches[0][0]));
                }
                $offset = $matches[0][1] + strlen($matches[0][0]);
            } else {
                $keep_going = false;
            }
        }

    }


}
