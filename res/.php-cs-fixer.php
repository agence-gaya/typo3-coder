<?php

$config = new \PhpCsFixer\Config();
$config->setRiskyAllowed(true);
$config->setRules([
    '@DoctrineAnnotation' => true,
    '@PER-CS2.0' => true,
    '@PHP82Migration' => true,
    'array_syntax' => ['syntax' => 'short'],
    'declare_equal_normalize' => ['space' => 'none'],
    'declare_parentheses' => true,
    'declare_strict_types' => true,
    'dir_constant' => true,
    'fully_qualified_strict_types' => true,
    'function_to_constant' => ['functions' => ['get_called_class', 'get_class', 'get_class_this', 'php_sapi_name', 'phpversion', 'pi']],
    'type_declaration_spaces' => true,
    'global_namespace_import' => ['import_classes' => true, 'import_constants' => false, 'import_functions' => false],
    'list_syntax' => ['syntax' => 'short'],
    'modernize_strpos' => true,
    'modernize_types_casting' => true,
    'native_function_casing' => true,
    'no_alias_functions' => true,
    'no_blank_lines_after_phpdoc' => true,
    'no_empty_phpdoc' => true,
    'no_empty_statement' => true,
    'no_extra_blank_lines' => true,
    'no_leading_namespace_whitespace' => true,
    'no_null_property_initialization' => true,
    'no_short_bool_cast' => true,
    'no_singleline_whitespace_before_semicolons' => true,
    'no_superfluous_elseif' => true,
    'no_trailing_comma_in_singleline' => true,
    'no_unneeded_control_parentheses' => true,
    'no_unneeded_import_alias' => true,
    'no_unused_imports' => true,
    'no_useless_else' => true,
    'no_useless_nullsafe_operator' => true,
    'nullable_type_declaration' => ['syntax' => 'question_mark'],
    'nullable_type_declaration_for_default_null_value' => true,
    'ordered_imports' => ['imports_order' => ['class', 'function', 'const'], 'sort_algorithm' => 'alpha'],
    'php_unit_construct' => ['assertions' => ['assertEquals', 'assertSame', 'assertNotEquals', 'assertNotSame']],
    'php_unit_mock_short_will_return' => true,
    'php_unit_test_case_static_method_calls' => ['call_type' => 'self'],
    'phpdoc_align' => true,
    'phpdoc_annotation_without_dot' => true,
    'phpdoc_indent' => true,
    'phpdoc_inline_tag_normalizer' => true,
    'phpdoc_line_span' => true,
    'phpdoc_no_access' => true,
    'phpdoc_no_empty_return' => true,
    'phpdoc_no_package' => true,
    'phpdoc_no_useless_inheritdoc' => true,
    'phpdoc_order' => true,
    'phpdoc_order_by_value' => true,
    'phpdoc_separation' => true,
    'phpdoc_scalar' => true,
    'phpdoc_single_line_var_spacing' => true,
    'phpdoc_summary' => true,
    'phpdoc_tag_casing' => true,
    'phpdoc_tag_type' => true,
    'phpdoc_to_comment' => ['ignored_tags' => ['phpstan-ignore-line', 'phpstan-ignore-next-line', 'todo']],
    'phpdoc_trim' => true,
    'phpdoc_trim_consecutive_blank_line_separation' => true,
    'phpdoc_types' => true,
    'phpdoc_types_order' => ['null_adjustment' => 'always_last', 'sort_algorithm' => 'alpha'],
    'return_type_declaration' => ['space_before' => 'none'],
    'phpdoc_var_annotation_correct_order' => true,
    'phpdoc_var_without_name' => true,
    'self_accessor' => true,
    'single_line_comment_style' => ['comment_types' => ['hash']],
    'single_quote' => true,
    'whitespace_after_comma_in_array' => ['ensure_single_space' => true],
    'yoda_style' => ['equal' => false, 'identical' => false, 'less_and_greater' => false],
]);

$config->getFinder()
    ->exclude('.build')
    ->exclude('templates')
    ->exclude('tests/Unit/Fixtures')
    ->in(__DIR__ . '/packages');

if (file_exists('.php-cs-fixer.project.php')) {
    include_once '.php-cs-fixer.project.php';
}

return $config;
