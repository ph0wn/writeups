Pico refuses to drive his brand new 100k car without being sure that safety systems are really totally safe.
In particular, Pico wants to be totally sure that the **ADAS** system is safe. ADAS stands for *Advanced Driver-Assistance System*.

Pico manages to get a reachability graph of this ADAS system. A reachability graph represents all the execution paths that are possible in the system, starting from an initial state.

One way to store such a graph is to use the AUT format, defined as follows:
des (<initialState>, <numberOfTransitions>, <numberOfStates>)
(<sourceState>, "<transitionLabel>", <destinationState>)
(<sourceState>, "<transitionLabel>", <destinationState>)


For instance, let us consider an example graph:
des(0, 5, 4)
(0, a, 1)
(0, b, 2)
(2, c, 1)
(1, d, 3)
(3, e, 0)


Let us represent this graph (can easily be done by hand)
        v
      ->0 --b--> 2
      |	\        |
      |  a       | 
      e   \      |
      |	   v     |
      3<-d--1<--c-
	  
The same graph is represented in the PNG below:

![](./simplegraph.png)

This graph features all possible execution paths. An execution paths is a list of transitions, starting from the initial state, and stopping whenever a state of the path has no outgoing transition (also known as a deadlock state). In the example graph, all execution paths are infinite (there is no dead lock), that is all states have at least one outgoing transition. 

This graph has many possible execution paths, for instance:
transition b, then transition c, then d, then a then d, then e, then b, etc. which gives the execution path: bcdeadb... (infinitely)
Another possible execution path: adeadebcdeadebcade... (infinitely)

Also, bcd is not an execution path since from state "3", it is possible to execute "e".

In the reachability graph we provide, there are two kinds of labels:

- Internal actions in components of the ADAS. For instance, "component1/x=x+1" means that component1 has made the following action: x=x+1
- Exchange of information between a sending component and a receiving component: "!sentMessage?receivedMessage(information)




