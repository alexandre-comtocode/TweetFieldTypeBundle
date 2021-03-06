<?php
/**
 * File containing the Tweet FieldType Test class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace EzSystems\TweetFieldTypeBundle\Tests\eZ\Publish\FieldType;

use eZ\Publish\API\Repository\Values\ContentType\FieldDefinition;
use eZ\Publish\Core\FieldType\Tests\FieldTypeTest;
use eZ\Publish\Core\FieldType\ValidationError;
use eZ\Publish\SPI\FieldType\Value as SPIValue;
use EzSystems\TweetFieldTypeBundle\eZ\Publish\FieldType\Tweet\Type as TweetType;
use EzSystems\TweetFieldTypeBundle\eZ\Publish\FieldType\Tweet\Value as TweetValue;
use EzSystems\TweetFieldTypeBundle\Twitter\TwitterClientInterface;
use eZ\Publish\Core\Base\Exceptions\InvalidArgumentException;

/**
 * Class TweetTest for testing the Tweet\Type class
 *
 * To run these tests execute the following command:
 * ```bash
 * phpunit src/EzSystems/TweetFieldTypeBundle/Tests/eZ/Publish/FieldType/TweetTest.php
 * ```
 */
class TweetTest extends FieldTypeTest
{
    /**
     * @var \EzSystems\TweetFieldTypeBundle\Twitter\TwitterClientInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $twitterClientMock;

    /**
     * @var FieldDefinition|\PHPUnit_Framework_MockObject_MockObject
     */
    private $fieldDefinitionMock;

    protected function createFieldTypeUnderTest()
    {
        return new TweetType($this->getTwitterClientMock());
    }

    protected function getValidatorConfigurationSchemaExpectation()
    {
        return [
            'TweetValueValidator' => [
                'authorList' => [
                    'type' => 'array',
                    'default' => []
                ]
            ]
        ];
    }

    protected function getSettingsSchemaExpectation()
    {
        return [];
    }

    protected function getEmptyValueExpectation()
    {
        return new TweetValue;
    }

    public function provideInvalidInputForAcceptValue()
    {
        return [
            [
                1,
                InvalidArgumentException::class,
            ],
            [
                new \stdClass,
                InvalidArgumentException::class
            ],
        ];
    }

    public function provideValidInputForAcceptValue()
    {
        return [
            [
                'https://twitter.com/user/status/123456789',
                new TweetValue(['url' => 'https://twitter.com/user/status/123456789']),
            ],
            [
                new TweetValue(
                    [
                        'url' => 'https://twitter.com/user/status/123456789'
                    ]
                ),
                new TweetValue(
                    [
                        'url' => 'https://twitter.com/user/status/123456789'
                    ]
                ),
            ],
            [
                new TweetValue(
                    [
                        'url' => 'https://twitter.com/user/status/123456789',
                        'authorUrl' => 'https://twitter.com/user',
                        'contents' => '<blockquote />'
                    ]
                ),
                new TweetValue(
                    [
                        'url' => 'https://twitter.com/user/status/123456789',
                        'authorUrl' => 'https://twitter.com/user',
                        'contents' => '<blockquote />'
                    ]
                )
            ]
        ];
    }

    public function provideInputForToHash()
    {
        return [
            [
                new TweetValue,
                null
            ],
            [
                new TweetValue(['url' => 'https://twitter.com/user/status/123456789']),
                [
                    'url' => 'https://twitter.com/user/status/123456789',
                    'authorUrl' => '',
                    'contents' => ''
                ]
            ],
            [
                new TweetValue(
                    [
                        'url' => 'https://twitter.com/user/status/123456789',
                        'authorUrl' => 'https://twitter.com/user',
                        'contents' => '<blockquote />'
                    ]
                ),
                [
                    'url' => 'https://twitter.com/user/status/123456789',
                    'authorUrl' => 'https://twitter.com/user',
                    'contents' => '<blockquote />'
                ]
            ]
        ];
    }

    public function provideInputForFromHash()
    {
        return [
            [
                [],
                new TweetValue
            ],
            [
                ['url' => 'https://twitter.com/user/status/123456789'],
                new TweetValue(['url' => 'https://twitter.com/user/status/123456789']),
            ],
            [
                [
                    'url' => 'https://twitter.com/user/status/123456789',
                    'authorUrl' => 'https://twitter.com/user',
                    'contents' => '<blockquote />'
                ],
                new TweetValue(
                    [
                        'url' => 'https://twitter.com/user/status/123456789',
                        'authorUrl' => 'https://twitter.com/user',
                        'contents' => '<blockquote />'
                    ]
                ),
            ]
        ];
    }

    protected function provideFieldTypeIdentifier()
    {
        return 'eztweet';
    }

    /**
     * @dataProvider provideDataForGetName
     *
     * @param SPIValue $value
     * @param string $expected
     *
     * @expectedException \RuntimeException
     */
    public function testGetName(SPIValue $value, $expected)
    {
        $this->getFieldTypeUnderTest()->getName($value);
    }

    public function provideDataForGetName()
    {
        return [
            [$this->getEmptyValueExpectation(), ''],
            [new TweetValue('https://twitter.com/user/status/123456789'), 'user-123456789'],
        ];
    }

    /**
     * @return \EzSystems\TweetFieldTypeBundle\Twitter\TwitterClientInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getTwitterClientMock()
    {
        if ($this->twitterClientMock === null) {
            $this->twitterClientMock = $this->getMockBuilder(TwitterClientInterface::class)->getMock();
        }

        return $this->twitterClientMock;
    }

    public function provideValidDataForValidate()
    {
        return [
            [
                [
                    'validatorConfiguration' => [
                        'TweetValueValidator' => [
                            'authorList' => ['user']
                        ]
                    ]
                ],
                new TweetValue('https://twitter.com/user/status/123456789')
            ],
            [
                [
                    'validatorConfiguration' => [
                        'TweetValueValidator' => [
                            'authorList' => ['anotherUser', 'user']
                        ]
                    ]
                ],
                new TweetValue('https://twitter.com/user/status/123456789')
            ],
            [
                [
                    'validatorConfiguration' => [
                        'TweetValueValidator' => [
                            'authorList' => []
                        ]
                    ]
                ],
                new TweetValue('https://twitter.com/user/status/123456789')
            ]
        ];
    }

    public function provideInvalidDataForValidate()
    {
        return [
            [
                [
                    'validatorConfiguration' => [
                        'TweetValueValidator' => [
                            'authorList' => ['anotherUser']
                        ]
                    ]
                ],
                new TweetValue('https://twitter.com/user/status/123456789'),
                [
                    new ValidationError(
                        'Twitter user %user% is not in the approved author list',
                        null,
                        ['%user%' => 'user']
                    )
                ]
            ],
            [
                [
                    'validatorConfiguration' => [
                        'TweetValueValidator' => [
                            'authorList' => ['user']
                        ]
                    ]
                ],
                new TweetValue('https://test.com/user/status/123456789'),
                [
                    new ValidationError(
                        'Invalid Twitter status URL %url%',
                        null,
                        ['%url%' => 'https://test.com/user/status/123456789']
                    )
                ]
            ]
        ];
    }

    /**
     * @dataProvider provideDataForGetName
     *
     * @param SPIValue $value
     * @param string $expected
     */
    public function testGetFieldName(SPIValue $value, $expected)
    {
        $fieldDefinitionMock = $this->getFieldDefinitionMock();
        $languageCodeDummy = '';

        self::assertSame(
            $expected,
            $this->getFieldTypeUnderTest()->getFieldName($value, $fieldDefinitionMock, $languageCodeDummy)
        );
    }

    /**
     * @return FieldDefinition|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getFieldDefinitionMock()
    {
        if ($this->fieldDefinitionMock === null) {
            $this->fieldDefinitionMock = $this->getMockBuilder(FieldDefinition::class)->getMock();
        }

        return $this->fieldDefinitionMock;
    }
}
