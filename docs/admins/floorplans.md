Floorplan Configuration
=

An example of floorplan configuration is available in `/etc/tine20/conf.d/floorplan.inc.php.dist` file.

Explanation
---
~~~php
'floorplans' => [[
    'name' => '<floor-name>',
    'image' => '<image-path>',
    'resources' => [[
        'eventSaveLocation' => '<RESOURCE_CAL|PERSONAL_CAL>',
        'resourceName' => '<resource-name>',
        'resourceDisplayName' => '<resource-display-name>',
        'polygon' => [[ [677, 88], [677, 120], [742, 120], [742, 88]]] // or path
    ],
    'referenceImageDim' => [[1000, 1353]]
]]
~~~

- Each element of `floorplans` list represent a floor.
- Each element of `resources` within a floor, represent a resource that is reservable on this floor.
- For each of the resources in `resources` array, a resource with the name `<resource-name>` needs to be created.
This can be done through `coredata>'Application Data'>'Calendar'>'Resources'`
- The possible options for `eventSaveLocation` are `RESOURCE_CAL` and `PERSONAL_CAL`. This specifies the container in which
the reservation made on this resource is saved.
- `resourceDisplayName` is the name displayed in the UI.
- `polygon` is a list of list of pixel co-ordinates of polygon edges that enclose an area on the image belonging to the resource. The
co-ordinates have to be in __Screen Coordinate System__, i.e. with origin in upper-left corner, X-axis oriented left-to-right,
and Y-axis top-to-bottom. _NOTE_: the co-ordinates list has to be ordered, along the perimeter of the polygon.
- `referenceImageDim` specifies the dimension of image using which the polygon pixel co-ordinates were taken.