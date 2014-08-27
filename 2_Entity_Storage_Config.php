<?php
class CreateStorageConfigCommand extends ContainerAwareCommand
{
    // ...

    /**
     * parse the contents of the @storage part of the given doc comment
     * @param string $docComment
     * @return array see example
     * @example example return for class comment:
     * array(1) { ["table"]=> string(21) "entity_table_name" }
     * example return for property comment:
     * array(1) { ["type"]=> string(3) "int" }
     */
    protected function parseStorageComment($docComment)
    {
        $storagePattern = "/@storage\((.*?)\)/i";
        $count = preg_match($storagePattern, $docComment, $matches);
        // no @storage part found
        if ($count <= 0)
        {
            return false;
        }
        $result = array();
        $pairs = explode(", ", $matches[1]);
        foreach ($pairs as $pair)
        {
            $parts = explode("=", $pair);
            $result[$parts[0]] = $parts[1];
        }
        return $result;
    }

    protected function createStorageConfig($classname)
    {
        $classConfig = array();
        $fieldConfig = array();
        try
        {
            $reflect = new \ReflectionClass($classname);
            $classConfig = $this->parseStorageComment($reflect->getDocComment());
            $properties = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);
            foreach ($properties as $property)
            {
                $docComment = $property->getDocComment();
                $result = $this->parseStorageComment($docComment);
                if ($result)
                {
                    $fieldConfig[$property->name] = $result;
                }
            }
        }
        catch(ReflectionException $e)
        {
            $this->writeln("<error>Failed to parse config of " . $classname . ". " . $e->getMessage() . "</error>");
        }
        $config = array(
            "class" => $classConfig,
            "fields" => $fieldConfig
        );
        $cacheKey = "jf:orm:" . str_replace("\\", ":", $classname) . ":storage:config";
        $this->getContainer()->get("jf_cache.cacheManager")->set($cacheKey, $config);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $classname = $input->getOption('class');
        $output->writeln("- Create storage config for " . $classname);
        $this->createStorageConfig($classname);
        $output->writeln("- Cache it. Done.");
    }
}