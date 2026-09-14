<?php
// php-cs-fixer fix --config=tools/php-cs-fixer.php   (PHP CS Fixer 3.91, PHP 8.1+)
//
// The rules the PrestaShop Addons validator reported for the module (Standards
// step), with PrestaShop's / Symfony's settings - and nothing that would need
// a newer PHP than the module supports (5.6): no const visibility, no
// trailing commas outside arrays, and no non_printable_character (it writes
// non-breaking spaces as {a0}, which PHP 5.6 does not read; the French
// texts use plain spaces instead).
$root = dirname(__DIR__);

$finder = PhpCsFixer\Finder::create()
    ->in($root)
    ->exclude(array('docs', 'tools', 'website', 'marketing', 'relay-server', '.claude', 'node_modules'))
    ->notName('live-verify.php')
    ->name('*.php')
    ->ignoreDotFiles(true);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setUsingCache(false)
    ->setFinder($finder)
    ->setRules(array(
        'blank_line_after_opening_tag' => true,
        'array_syntax' => array('syntax' => 'short'),
        'phpdoc_separation' => true,
        'blank_line_before_statement' => array('statements' => array('return')),
        'phpdoc_to_comment' => array('allow_before_return_statement' => false),
        'no_null_property_initialization' => true,
        'increment_style' => true,
        'modifier_keywords' => array('elements' => array('method', 'property')),
        'phpdoc_align' => array('align' => 'left'),
        'no_blank_lines_after_phpdoc' => true,
        'align_multiline_comment' => true,
        'binary_operator_spaces' => true,
        'phpdoc_annotation_without_dot' => true,
        'array_indentation' => true,
        'standardize_increment' => true,
        'trailing_comma_in_multiline' => array('elements' => array('arrays')),
        'class_attributes_separation' => array('elements' => array('method' => 'one')),
        'no_extra_blank_lines' => array('tokens' => array('attribute', 'case', 'continue', 'curly_brace_block', 'default', 'extra', 'parenthesis_brace_block', 'square_brace_block', 'switch', 'throw', 'use')),
        'statement_indentation' => true,
        'single_space_around_construct' => true,
        'no_spaces_after_function_name' => true,
        'include' => true,
    ));
