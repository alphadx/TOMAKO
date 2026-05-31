<?php
declare(strict_types=1);

namespace app\models\search;

trait SearchParamsSanitizerTrait
{
    protected function loadSanitized(array $params): bool
    {
        $formName = $this->formName();

        if ($formName === '') {
            $params = $this->sanitizeAttributesArray($params);
            return $this->load($params, '');
        }

        if (isset($params[$formName]) && is_array($params[$formName])) {
            $params[$formName] = $this->sanitizeAttributesArray($params[$formName]);
        }

        return $this->load($params, $formName);
    }

    private function sanitizeAttributesArray(array $attributes): array
    {
        foreach ($attributes as $name => $value) {
            if ($value === '' && $this->emptyStringShouldBeNull((string) $name)) {
                $attributes[$name] = null;
            }
        }

        return $attributes;
    }

    private function emptyStringShouldBeNull(string $attribute): bool
    {
        if (!property_exists($this, $attribute)) {
            return false;
        }

        try {
            $property = new \ReflectionProperty($this, $attribute);
        } catch (\ReflectionException) {
            return false;
        }

        $type = $property->getType();
        if ($type === null) {
            return false;
        }

        $namedTypes = [];

        if ($type instanceof \ReflectionNamedType) {
            $namedTypes[] = $type;
        } elseif ($type instanceof \ReflectionUnionType) {
            foreach ($type->getTypes() as $unionType) {
                if ($unionType instanceof \ReflectionNamedType) {
                    $namedTypes[] = $unionType;
                }
            }
        }

        if ($namedTypes === []) {
            return false;
        }

        foreach ($namedTypes as $namedType) {
            if (!$namedType->isBuiltin()) {
                continue;
            }

            $typeName = $namedType->getName();
            if ($typeName === 'string' || $typeName === 'array') {
                return false;
            }
        }

        foreach ($namedTypes as $namedType) {
            if (!$namedType->isBuiltin()) {
                continue;
            }

            if (in_array($namedType->getName(), ['int', 'float', 'bool'], true)) {
                return true;
            }
        }

        return false;
    }
}
