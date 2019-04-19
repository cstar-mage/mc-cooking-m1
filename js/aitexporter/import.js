
/**
 * Orders Export and Import
 *
 * @category:    Aitoc
 * @package:     Aitoc_Aitexporter
 * @version      1.2.11
 * @license:     n/a
 * @copyright:   Copyright (c) 2015 AITOC, Inc. (http://www.aitoc.com)
 */
Event.observe(document, 'dom:loaded', function()
{
    function toggleParseType()
    {
        switch ($('parse_type').value)
        {
            case 'csv':
                $('parse_delimiter').parentNode.parentNode.show();
                $('parse_enclose').parentNode.parentNode.show();
                break;

            case 'xml':
            default:
                $('parse_delimiter').parentNode.parentNode.hide();
                $('parse_enclose').parentNode.parentNode.hide();
                break;
        }
    }

    Event.observe($('parse_type'), 'change', toggleParseType);
    toggleParseType();
});