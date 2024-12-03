import java.io.*;
import java.util.ArrayList;

public class State {

    public int id;
    public ArrayList<Transition> outputTransitions;

    public State() {
        outputTransitions = new ArrayList<>();
    }

}
