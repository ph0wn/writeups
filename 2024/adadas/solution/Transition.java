import java.io.*;

public class Transition {

    public State originState;
    public State destinationState;
    public String tag;

    public Transition(State _originState, String _tag, State _destinationState) {
        originState = _originState;
        tag = _tag;
        destinationState = _destinationState;

        originState.outputTransitions.add(this);
    }

}
