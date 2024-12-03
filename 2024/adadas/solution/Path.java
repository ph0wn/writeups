import java.io.*;
import java.util.HashSet;

public class Path {

    public State currentState;
    public HashSet<Integer> metStates;

    public Path(State s) {
        currentState = s;
        metStates = new HashSet<>();
        metStates.add(s.id);
    }

    public Path() {
        metStates = new HashSet<>();
    }

    public void add(Integer i) {
        metStates.add(i);
    }

    public void remove(Integer i) {
        metStates.remove(i);
    }
    public Path duplicate() {
        Path newPath = new Path();
        newPath.currentState = currentState;
        metStates.addAll(metStates);
        return newPath;
    }

    public boolean contains(Integer id) {
        return metStates.contains(id);
    }

    public String toString() {
        return metStates.toString();
    }

}
