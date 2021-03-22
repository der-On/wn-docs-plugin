<?php namespace Winter\Docs\Classes;

use File;

class EventParser {

    protected static $docBlockFactory;

    public static function getEvents($path, $prefix = null)
    {
        $events = [];

        static::$docBlockFactory = \phpDocumentor\Reflection\DocBlockFactory::createInstance();

        foreach (File::allFiles($path) as $file) {
            if ($fileEvents = static::getEvent($file, $prefix)) {
                $events = array_merge($events, $fileEvents);
            }
        }

        return $events;
    }

    public static function getEvent($file, $prefix = null)
    {
        $fileEvents = [];

        $segments = explode('/', $prefix . $file->getRelativePathName());
        $trigger = implode('\\', array_map('ucfirst', $segments));

        $data = file_get_contents($file->getPathName());

        if (!preg_match_all('| +/\*\*\s+\* @event.+\*/|Us', $data, $match)) {
            return null;
        }

        foreach ($match[0] as $doc) {
            $docblock = static::$docBlockFactory->create(static::fixDocBlock($doc));
            $fileEvents[] = [
                'triggeredIn' => $trigger,
                'eventName' => $event = $docblock->getTagsByName('event')[0]->getDescription()->render(),
                'summary' => $docblock->getSummary(),
                'description' => $docblock->getDescription()->render(),
            ];
        }
        return $fileEvents;
    }

    protected static function fixDocBlock($doc)
    {
        $parts = explode("\n", $doc);

        // extract event line
        $event = array_splice($parts, 1, 1);

        // insert before comment closing
        array_splice($parts, count($parts)-1, 0, (array)$event);

        return implode("\n", $parts);
    }
}