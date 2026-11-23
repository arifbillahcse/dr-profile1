<?php

return [
    'modules' => [
        'slider' => [
            'heading' => 'Sliders',
            'singular' => 'slider',
            'title_field' => 'title',
            'search' => [
                'title',
                'caption',
            ],
            'image_column' => 'image_id',
            'fields' => [
                'title' => [
                    'label' => 'Title',
                    'type' => 'text',
                    'rules' => [
                        'nullable',
                        'string',
                        'max:255',
                    ],
                ],
                'caption' => [
                    'label' => 'Caption',
                    'type' => 'textarea',
                    'rules' => [
                        'nullable',
                        'string',
                        'max:5000',
                    ],
                ],
                'image_id' => [
                    'label' => 'Slider image',
                    'type' => 'image',
                    'required' => true,
                    'rules' => [
                        'required',
                        'integer',
                    ],
                ],
            ],
        ],
        'featured' => [
            'heading' => 'Featured statistics',
            'singular' => 'statistic',
            'title_field' => 'label',
            'search' => [
                'label',
                'value',
            ],
            'fields' => [
                'label' => [
                    'label' => 'Label',
                    'required' => true,
                    'rules' => [
                        'required',
                        'string',
                        'max:255',
                    ],
                ],
                'value' => [
                    'label' => 'Display value',
                    'required' => true,
                    'max' => 50,
                    'hint' => 'For example: 2000, 18 or 95.',
                    'rules' => [
                        'required',
                        'string',
                        'max:50',
                    ],
                ],
                'suffix' => [
                    'label' => 'Suffix',
                    'max' => 20,
                    'hint' => 'For example: + or %.',
                    'rules' => [
                        'nullable',
                        'string',
                        'max:20',
                    ],
                ],
                'icon' => [
                    'label' => 'Icon class',
                    'type' => 'icon',
                ],
            ],
        ],
        'service' => [
            'heading' => 'Services',
            'singular' => 'service',
            'title_field' => 'name',
            'search' => [
                'name',
                'description',
            ],
            'image_column' => 'image_id',
            'fields' => [
                'name' => [
                    'label' => 'Service name',
                    'required' => true,
                    'rules' => [
                        'required',
                        'string',
                        'max:255',
                    ],
                ],
                'slug' => [
                    'label' => 'URL slug',
                    'hint' => 'Leave empty to generate from the name. Use lowercase letters, numbers and hyphens.',
                    'rules' => [
                        'required',
                        'string',
                        'max:255',
                        'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                    ],
                ],
                'description' => [
                    'label' => 'Description',
                    'type' => 'textarea',
                    'required' => true,
                    'max' => 10000,
                    'rules' => [
                        'required',
                        'string',
                        'max:10000',
                    ],
                ],
                'icon' => [
                    'label' => 'Icon class',
                    'type' => 'icon',
                ],
                'image_id' => [
                    'label' => 'Image',
                    'type' => 'image',
                    'rules' => [
                        'nullable',
                        'integer',
                    ],
                ],
            ],
        ],
        'education' => [
            'heading' => 'Education',
            'singular' => 'qualification',
            'title_field' => 'qualification',
            'search' => [
                'qualification',
                'institution',
            ],
            'image_column' => 'image_id',
            'fields' => [
                'qualification' => [
                    'label' => 'Qualification',
                    'required' => true,
                    'rules' => [
                        'required',
                        'string',
                        'max:255',
                    ],
                ],
                'institution' => [
                    'label' => 'Institution',
                    'rules' => [
                        'nullable',
                        'string',
                        'max:255',
                    ],
                ],
                'year' => [
                    'label' => 'Year',
                    'type' => 'number',
                    'min' => 1900,
                    'maxValue' => 2100,
                    'rules' => [
                        'nullable',
                        'integer',
                        'between:1900,2100',
                    ],
                ],
                'description' => [
                    'label' => 'Description',
                    'type' => 'textarea',
                    'max' => 10000,
                    'rules' => [
                        'nullable',
                        'string',
                        'max:10000',
                    ],
                ],
                'image_id' => [
                    'label' => 'Image',
                    'type' => 'image',
                    'rules' => [
                        'nullable',
                        'integer',
                    ],
                ],
            ],
        ],
        'award' => [
            'heading' => 'Awards',
            'singular' => 'award',
            'title_field' => 'title',
            'search' => [
                'title',
                'organization',
            ],
            'image_column' => 'image_id',
            'fields' => [
                'title' => [
                    'label' => 'Award title',
                    'required' => true,
                    'rules' => [
                        'required',
                        'string',
                        'max:255',
                    ],
                ],
                'organization' => [
                    'label' => 'Organization',
                    'rules' => [
                        'nullable',
                        'string',
                        'max:255',
                    ],
                ],
                'year' => [
                    'label' => 'Year',
                    'type' => 'number',
                    'min' => 1900,
                    'maxValue' => 2100,
                    'rules' => [
                        'nullable',
                        'integer',
                        'between:1900,2100',
                    ],
                ],
                'description' => [
                    'label' => 'Description',
                    'type' => 'textarea',
                    'max' => 10000,
                    'rules' => [
                        'nullable',
                        'string',
                        'max:10000',
                    ],
                ],
                'image_id' => [
                    'label' => 'Image',
                    'type' => 'image',
                    'rules' => [
                        'nullable',
                        'integer',
                    ],
                ],
            ],
        ],
        'gallery' => [
            'heading' => 'Gallery',
            'singular' => 'gallery image',
            'title_field' => 'title',
            'search' => [
                'title',
                'caption',
            ],
            'image_column' => 'image_id',
            'fields' => [
                'title' => [
                    'label' => 'Image title',
                    'required' => true,
                    'rules' => [
                        'required',
                        'string',
                        'max:255',
                    ],
                ],
                'caption' => [
                    'label' => 'Caption',
                    'type' => 'textarea',
                    'rules' => [
                        'nullable',
                        'string',
                        'max:5000',
                    ],
                ],
                'image_id' => [
                    'label' => 'Gallery image',
                    'type' => 'image',
                    'required' => true,
                    'rules' => [
                        'required',
                        'integer',
                    ],
                ],
            ],
        ],
        'faq' => [
            'heading' => 'Frequently asked questions',
            'singular' => 'question',
            'title_field' => 'question',
            'search' => [
                'question',
                'answer',
            ],
            'fields' => [
                'question' => [
                    'label' => 'Question',
                    'required' => true,
                    'type' => 'textarea',
                    'max' => 2000,
                    'rules' => [
                        'required',
                        'string',
                        'max:2000',
                    ],
                ],
                'answer' => [
                    'label' => 'Answer',
                    'required' => true,
                    'type' => 'textarea',
                    'rows' => 7,
                    'max' => 20000,
                    'rules' => [
                        'required',
                        'string',
                        'max:20000',
                    ],
                ],
            ],
        ],
        'youtube' => [
            'heading' => 'YouTube videos',
            'singular' => 'video',
            'title_field' => 'title',
            'search' => [
                'title',
                'description',
            ],
            'image_column' => 'thumbnail_image_id',
            'fields' => [
                'title' => [
                    'label' => 'Video title',
                    'required' => true,
                    'rules' => [
                        'required',
                        'string',
                        'max:255',
                    ],
                ],
                'youtube_id' => [
                    'label' => 'YouTube video',
                    'required' => true,
                    'hint' => 'Paste a YouTube link or an 11-character video ID.',
                    'rules' => [
                        'required',
                        'string',
                        'regex:/^[A-Za-z0-9_-]{11}$/',
                    ],
                ],
                'description' => [
                    'label' => 'Description',
                    'type' => 'textarea',
                    'rules' => [
                        'nullable',
                        'string',
                        'max:5000',
                    ],
                ],
                'thumbnail_image_id' => [
                    'label' => 'Custom thumbnail',
                    'type' => 'image',
                    'rules' => [
                        'nullable',
                        'integer',
                    ],
                ],
            ],
        ],
        'experience' => [
            'heading' => 'Professional experience',
            'singular' => 'experience',
            'title_field' => 'institution',
            'search' => [
                'institution',
                'position',
            ],
            'image_column' => 'image_id',
            'fields' => [
                'institution' => [
                    'label' => 'Institution',
                    'required' => true,
                    'rules' => [
                        'required',
                        'string',
                        'max:255',
                    ],
                ],
                'position' => [
                    'label' => 'Position',
                    'required' => true,
                    'rules' => [
                        'required',
                        'string',
                        'max:255',
                    ],
                ],
                'period_label' => ['label' => 'Period label', 'hint' => 'Optional, for example 2026 or 2020–present. Use this when exact dates are unknown.', 'max' => 100, 'rules' => ['nullable', 'string', 'max:100']],
                'started_on' => [
                    'label' => 'Start date',
                    'type' => 'date',
                    'rules' => [
                        'nullable',
                        'required_with:ended_on',
                        'date_format:Y-m-d',
                    ],
                ],
                'ended_on' => [
                    'label' => 'End date',
                    'type' => 'date',
                    'rules' => [
                        'nullable',
                        'date_format:Y-m-d',
                        'after_or_equal:started_on',
                        'prohibited_if:is_current,1',
                    ],
                ],
                'is_current' => [
                    'label' => 'This is a current position',
                    'type' => 'checkbox',
                    'rules' => [
                        'sometimes',
                        'boolean',
                    ],
                ],
                'description' => [
                    'label' => 'Description',
                    'type' => 'textarea',
                    'max' => 10000,
                    'rules' => [
                        'nullable',
                        'string',
                        'max:10000',
                    ],
                ],
                'image_id' => [
                    'label' => 'Image',
                    'type' => 'image',
                    'rules' => [
                        'nullable',
                        'integer',
                    ],
                ],
            ],
        ],
        'chamber' => [
            'heading' => 'Chambers',
            'singular' => 'chamber',
            'title_field' => 'name',
            'search' => [
                'name',
                'address',
            ],
            'schedules' => true,
            'fields' => [
                'name' => [
                    'label' => 'Chamber name',
                    'required' => true,
                    'rules' => [
                        'required',
                        'string',
                        'max:255',
                    ],
                ],
                'address' => [
                    'label' => 'Address',
                    'required' => true,
                    'type' => 'textarea',
                    'rules' => [
                        'required',
                        'string',
                        'max:5000',
                    ],
                ],
                'phone' => [
                    'label' => 'Main phone number',
                    'type' => 'tel',
                    'max' => 50,
                    'rules' => [
                        'nullable',
                        'string',
                        'max:50',
                    ],
                ],
                'additional_phones_text' => [
                    'label' => 'Additional phone numbers',
                    'type' => 'textarea',
                    'hint' => 'Enter one phone number per line.',
                    'max' => 2000,
                    'rules' => [
                        'nullable',
                        'string',
                        'max:2000',
                    ],
                ],
                'email' => [
                    'label' => 'Chamber email',
                    'type' => 'email',
                    'rules' => ['nullable', 'email', 'max:254'],
                ],
                'map_url' => [
                    'label' => 'Map link',
                    'type' => 'url',
                    'max' => 2000,
                    'hint' => 'Use a complete HTTPS map link.',
                    'rules' => [
                        'nullable',
                        'url:https',
                        'max:2000',
                    ],
                ],
                'timezone' => [
                    'label' => 'Schedule timezone',
                    'type' => 'timezone',
                    'default' => 'Asia/Dhaka',
                    'required' => true,
                    'rules' => [
                        'required',
                        'timezone',
                    ],
                ],
            ],
        ],
        'process' => [
            'heading' => 'Consultation steps',
            'singular' => 'consultation step',
            'title_field' => 'title',
            'search' => [
                'title',
                'description',
            ],
            'fields' => [
                'title' => [
                    'label' => 'Step title',
                    'required' => true,
                    'rules' => [
                        'required',
                        'string',
                        'max:255',
                    ],
                ],
                'description' => [
                    'label' => 'Description',
                    'required' => true,
                    'type' => 'textarea',
                    'rules' => [
                        'required',
                        'string',
                        'max:5000',
                    ],
                ],
                'icon' => [
                    'label' => 'Icon class',
                    'type' => 'icon',
                ],
            ],
        ],
        'specialist' => [
            'heading' => 'Specialist highlights',
            'singular' => 'specialist highlight',
            'title_field' => 'title',
            'search' => [
                'title',
                'description',
            ],
            'fields' => [
                'title' => [
                    'label' => 'Title',
                    'required' => true,
                    'rules' => [
                        'required',
                        'string',
                        'max:255',
                    ],
                ],
                'description' => [
                    'label' => 'Description',
                    'type' => 'textarea',
                    'rules' => [
                        'nullable',
                        'string',
                        'max:5000',
                    ],
                ],
                'icon' => [
                    'label' => 'Icon class',
                    'type' => 'icon',
                ],
            ],
        ],
    ],
];
