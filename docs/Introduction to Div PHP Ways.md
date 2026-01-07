![[Div PHP Ways Overview.png]]
A "way" is different to a "route". We need a path for found a specific resource, but we need a way for do something. This library follow this concept when implements the routing and control of PHP application.

Ways is a class that adapts the concept of SOA to the architecture of a PHP application, and tries to integrate the parts of a hybrid system.

With Ways you should think more about "control points" than on controllers of an MVC pattern. Control points are activated when they are needed, ie on demand, depending on the definition you have made.

In addition to this, a control point may require the previous execution of another control point. You can also implement events or hooks, so you can execute one control point before or after another, without the latter knowing the existence of the first. These flexibilities are valid for example in a plugins architecture.

The control points can interact, and this means, redirect the flow to another, call control points directly, exchange data and url arguments, handle the output on screen, etc.

Div Ways is not only intended for the web but also for command line applications. Div Ways is implemented in a single class, in a single file. This allows quick start-up and easy adaptation with other platforms.