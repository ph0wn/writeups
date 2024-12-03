import java.io.BufferedReader;
import java.io.FileReader;
import java.io.IOException;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.HashSet;

public class Graph {

    public HashMap<Integer, State> states;
    public ArrayList<Transition> transitions;

    public Graph() {
        states = new HashMap<>();
        transitions = new ArrayList<>();
    }

    public int getNbOfStates() {
        return states.size();
    }

    public int getNbOfTransitions() {
        return transitions.size();
    }

    public int makeFromFile(String pathToFile) {
        try (BufferedReader reader = new BufferedReader(new FileReader(pathToFile))) {
            String line;

            int cpt = 0;
            // Read the file line by line until all lines are read
            while ((line = reader.readLine()) != null) {
                if (cpt % 10000 == 0) {
                    System.out.println("line: " + cpt);
                }
                cpt++;
                if (line.startsWith("(")) {

                    int originStateId, destStateId;
                    String tag;

                    line = line.substring(1, line.length() - 1);
                    String[] elts = line.split(",");
                    if (elts.length != 3) {
                        System.out.println("Skipping line: " + line);
                    } else {
                        originStateId = Integer.decode(elts[0]);
                        tag = elts[1];
                        destStateId = Integer.decode(elts[2]);

                        // Removing "
                        tag = tag.substring(1, tag.length() - 1);

                        State o = getStateCreate(originStateId);
                        State d = getStateCreate(destStateId);

                        Transition t = new Transition(o, tag, d);
                        transitions.add(t);

                    }

                } else {
                    System.out.println("Header line: " + line);
                }
            }
        } catch (IOException e) {
            System.out.println("An error occurred while reading the file:");
            e.printStackTrace();
        }

        return 0;
    }


    public State getStateCreate(int id) {
        State state = states.get(id);
        if (state != null) {
            return state;
        }

        State s = new State();
        s.id = id;
        states.put(id, s);
        return s;
    }

    public State getState(int id) {
        return states.get(id);

    }


    public boolean isReachabilitySatisfied(String tag) throws GraphException {
        for(Transition t: transitions) {
            if (t.tag.compareTo(tag) == 0) {
                return true;
            }
        }
        return false;
    }

    public boolean isLivenessSatisfied(String tag) throws GraphException {

        System.out.println("\nLiveness of " + tag + "?");

        State initialState = getState(0);

        if (initialState == null) {
            throw new GraphException("No initial state");
        }

        Path initialPath = new Path(initialState);

        HashSet<Integer> provedAsLived = new HashSet<>();

        return isLivenessSatisfied(tag, initialPath, provedAsLived);
    }



    public boolean isLivenessSatisfied(String tag, Path p, HashSet<Integer> provedAsLived) throws GraphException {
        
        State s = p.currentState;
        //System.out.println("Handling state " + s);
        int foundValid = 0;

        if (s.outputTransitions.size() == 0) {
            return false;
        }


        for (Transition tr : s.outputTransitions) {
            //System.out.println("Working with transition from " +  tr.originState.id  + " tag:" + tr.tag + " leading to " + tr.destinationState.id + " / tag=" + tag + " / equal ? " + tr.tag.startsWith(tag));
            if (tr.tag.startsWith(tag)) {
                //System.out.println("Tag found");
                // tag found!
                // We can stop working with this path: this path is valid
                foundValid ++;
            } else {
                // We figure out if the next state has already been met. If yes, we have a cycle without
                // the liveness
                State nextState = tr.destinationState;
                if (provedAsLived.contains(nextState.id)) {
                    foundValid++;
                } else {
                    if (p.contains(nextState.id)) {
                        //System.out.println("State " + nextState.id + " already met in path " + p);
                        return false;

                    } else {
                        //System.out.println("State " + nextState.id + " must be handled");

                        State currentState = p.currentState;
                        p.add(nextState.id);
                        p.currentState = nextState;
                        if (!isLivenessSatisfied(tag, p, provedAsLived)) {
                            return false;
                        }
                        foundValid++;
                        p.currentState = currentState;
                        p.remove(nextState.id);

                    }
                }
            }
        }

        boolean ret = (foundValid == s.outputTransitions.size());

        if (ret) {
            provedAsLived.add(p.currentState.id);
        }

        return ret;
    }


}
