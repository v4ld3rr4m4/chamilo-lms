<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Chamilo\SettingsBundle\Transformer\ArrayToIdentifierTransformer;
use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilderInterface;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class DocumentSettingsSchema
 * @package Chamilo\CoreBundle\Settings
 */
class DocumentSettingsSchema extends AbstractSettingsSchema
{
    /**
     * {@inheritdoc}
     */
    public function buildSettings(SettingsBuilderInterface $builder)
    {
        $builder
            ->setDefaults(
                array(
                    'default_document_quotum' => '100000000',
                    'default_group_quotum' => '100000000',
                    'permanently_remove_deleted_files' => 'false',
                    'upload_extensions_list_type' => 'blacklist',
                    'upload_extensions_blacklist' => '',
                    'upload_extensions_whitelist' => 'htm;html;jpg;jpeg;gif;png;swf;avi;mpg;mpeg;mov;flv;doc;docx;xls;xlsx;ppt;pptx;odt;odp;ods;pdf;webm;oga;ogg;ogv;h264',
                    'upload_extensions_skip' => 'true',
                    'upload_extensions_replace_by' => 'dangerous',
                    'permissions_for_new_directories' => '0777',
                    'permissions_for_new_files' => '0666',
                    'show_glossary_in_documents' => 'none',
                    'students_download_folders' => 'true',
                    'users_copy_files' => 'true',
                    'pdf_export_watermark_enable' => 'false',
                    'pdf_export_watermark_by_course' => 'false',
                    'pdf_export_watermark_text' => '',
                    'students_export2pdf' => 'true',
                    'show_users_folders' => 'true',
                    'show_default_folders' => 'true',
                    'enabled_text2audio' => 'false',
                    'enable_nanogong' => 'false',
                    'show_documents_preview' => 'false',
                    'enable_wami_record' => 'false',
                    'enable_webcam_clip' => 'false',
                    'tool_visible_by_default_at_creation' => [
                        'documents',
                        'learning_path',
                        'links',
                        'announcements',
                        'forums',
                        'quiz',
                        'gradebook'
                    ],
                    'documents_default_visibility_defined_in_course' => 'false', // ?
                    'allow_personal_user_files' => '', // ?
                    'if_file_exists_option' => 'rename'
                )
            )
            ->setTransformer(
                'tool_visible_by_default_at_creation',
                new ArrayToIdentifierTransformer()
            )
        ;

        $allowedTypes = array(
            'tool_visible_by_default_at_creation' => array('array'),
            'default_document_quotum' => array('string'),
            'default_group_quotum' => array('string')
        );
        $this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('default_document_quotum')
            ->add('default_group_quotum')
            ->add('permanently_remove_deleted_files', YesNoType::class)
            ->add(
                'upload_extensions_list_type',
                'choice',
                array(
                    'choices' => array(
                        'blacklist' => 'Blacklist',
                        'whitelist' => 'Whitelist',
                    ),
                )
            )
            ->add('upload_extensions_blacklist', 'textarea')
            ->add('upload_extensions_whitelist', 'textarea')
            ->add('upload_extensions_skip', 'textarea')
            ->add('upload_extensions_replace_by', 'textarea')
            ->add('permissions_for_new_directories')
            ->add('permissions_for_new_files')
            ->add(
                'show_glossary_in_documents',
                'choice',
                [
                    'choices' => [
                        'none' => 'ShowGlossaryInDocumentsIsNone',
                        'ismanual' => 'ShowGlossaryInDocumentsIsManual',
                        'isautomatic' => 'ShowGlossaryInDocumentsIsAutomatic',
                    ]
                ]
            )
            ->add('students_download_folders', YesNoType::class)
            ->add('users_copy_files', YesNoType::class)
            ->add('pdf_export_watermark_enable', YesNoType::class)
            ->add('pdf_export_watermark_by_course', YesNoType::class)
            ->add('pdf_export_watermark_text', 'textarea')
            ->add('students_export2pdf', YesNoType::class)
            ->add('show_users_folders', YesNoType::class)
            ->add('show_default_folders', YesNoType::class)
            ->add('enabled_text2audio', YesNoType::class)
            ->add('enable_nanogong', YesNoType::class)
            ->add('show_documents_preview', YesNoType::class)
            ->add('enable_wami_record', YesNoType::class)
            ->add('enable_webcam_clip', YesNoType::class)
            ->add(
                'tool_visible_by_default_at_creation',
                'choice',
                [
                    'multiple' => true,
                    'choices' => [
                        'Documents' => 'documents',
                        'LearningPath' => 'learning_path',
                        'Links' => 'links',
                        'Announcements' => 'announcements',
                        'Forums' => 'forums',
                        'Quiz' => 'quiz',
                        'Gradebook' => 'gradebook'
                    ]
                ]
            )
            ->add(
                'if_file_exists_option',
                'choice',
                [
                    'choices' => [
                        'rename' => 'Rename',
                        'overwrite' => 'Overwrite',
                    ]
                ]
            )
        ;
    }
}
