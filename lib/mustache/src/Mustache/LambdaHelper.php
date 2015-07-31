<?php

/*
 * This file is part of Mustache.php.
 *
 * (c) 2010-2014 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Mustache Lambda Helper.
 *
 * Passed as the second argument to section lambdas (higher order sections),
 * giving them access to a `render` method for rendering a string with the
 * current context.
 */
class Mustache_LambdaHelper
{
    private $mustache;
    private $context;
    private $arguments;

    /**
     * Mustache Lambda Helper constructor.
     *
     * @param Mustache_Engine  $mustache Mustache engine instance.
     * @param Mustache_Context $context  Rendering context.
     */
    public function __construct(Mustache_Engine $mustache, Mustache_Context $context)
    {
        $this->mustache = $mustache;
        $this->context  = $context;
    }

    /**
     * Render a string as a Mustache template with the current rendering context.
     *
     * @param string $string
     *
     * @return string Rendered template.
     */
    public function render($string)
    {
        return $this->mustache
            ->loadLambda((string) $string)
            ->renderInternal($this->context);
    }

    /**
     * Return the list of arguments for the current lambda.
     *
     * @return array Array of arguments.
     */
    public function getArguments() {
        return $this->arguments;
    }

    /**
     * Set the arguments in the lambda helper.
     *
     * @param array Array of arguments.
     */
    public function setArguments(array $arguments) {
        $this->arguments = $arguments;
    }
}
