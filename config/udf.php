<?php

return [
    'max_archive_size' => (int) env('UDF_MAX_ARCHIVE_SIZE', 20 * 1024 * 1024),
    'max_uncompressed_size' => (int) env('UDF_MAX_UNCOMPRESSED_SIZE', 100 * 1024 * 1024),
    'max_entries' => (int) env('UDF_MAX_ENTRIES', 100),
    'max_xml_size' => (int) env('UDF_MAX_XML_SIZE', 50 * 1024 * 1024),
    'max_document_nodes' => (int) env('UDF_MAX_DOCUMENT_NODES', 10000),
    'max_document_depth' => (int) env('UDF_MAX_DOCUMENT_DEPTH', 20),
    'max_text_length' => (int) env('UDF_MAX_TEXT_LENGTH', 5 * 1024 * 1024),
    'temporary_path' => storage_path('app/private/tmp/udf'),
];
