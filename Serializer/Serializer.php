<?php

namespace Opstalent\ApiBundle\Serializer;

use Symfony\Component\Serializer\Serializer as BaseSerializer;

/**
 * {@inheritDoc}
 */
class Serializer extends BaseSerializer
{
    /**
     * @param array $context
     * @param $data
     * @return array
     */
    protected function buildContext(array $context = [], $data)
    {
        if(array_key_exists("top", $context)
            && is_object($data)
        ) {
            unset($context["top"]);
            $context['entity'] = $data;
        }

        return $context;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($data, $format = null, array $context = array())
    {
        $context = $this->buildContext($context, $data);
        return parent::normalize($data, $format, $context);
    }
}
