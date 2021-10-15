<?php declare(strict_types = 1);
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\Latte\User\Macros;

use Latte\CompileException;
use Latte\Compiler;
use Latte\MacroNode;
use Latte\PhpWriter;
use Latte\Macros\MacroSet;

class Acl extends MacroSet
{
    /**
     * @param Compiler $compiler
     * @return Acl
     */
    static function install(Compiler $compiler): Acl
    {
        $me = new static($compiler);

        $me->addMacro('isAllowed', [$me, 'macroIsAllowed'], [$me, 'macroEndIsAllowed']);
        return $me;
    }

    /**
     * @param MacroNode $node
     * @param PhpWriter $writer
     * @return string
     * @throws CompileException
     */
    public function macroIsAllowed(MacroNode $node, PhpWriter $writer): string
    {
        if ($node->data->capture = ($node->args === '')) {
            return 'ob_start()';
        }
        if ($node->prefix === $node::PREFIX_TAG) {
            return $writer->write($node->htmlNode->closing ? 'if (array_pop($_l->isAlloweds)) {' : 'if ($_l->isAlloweds[] = (property_exists($this, "filters") ? call_user_func($this->filters->isAllowed, %node.args) : $template->getUserService()->isAllowed(%node.args))) {');
        }
        return $writer->write('if (property_exists($this, "filters") ? call_user_func($this->filters->isAllowed, %node.args) : $template->getUserService()->isAllowed(%node.args)) {');
    }

    /**
     * @param MacroNode $node
     * @param PhpWriter $writer
     * @return string
     * @throws CompileException
     */
    public function macroEndIsAllowed(MacroNode $node, PhpWriter $writer): string
    {
        if ($node->data->capture) {
            if ($node->args === '') {
                throw new CompileException('Missing condition in {if} macro.');
            }
            return $writer->write('if (property_exists($this, "filters") ? call_user_func($this->filters->isAllowed, %node.args) : $template->getUserService()->isAllowed(%node.args)) '
                . (isset($node->data->else) ? '{ ob_end_clean(); ob_end_flush(); }' : 'ob_end_flush();')
                . ' else '
                . (isset($node->data->else) ? '{ $_else = ob_get_contents(); ob_end_clean(); ob_end_clean(); echo $_else; }' : 'ob_end_clean();')
            );
        }
        return '}';
    }

}