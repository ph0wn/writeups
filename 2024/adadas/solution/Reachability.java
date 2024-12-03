import java.io.*;

public class Reachability {


    public static String loadFileData(File file) {
        char[] ba;
        try {
            InputStreamReader isr = new InputStreamReader(new FileInputStream(file), "UTF8");
            FileInputStream fis = new FileInputStream(file);
            int nb = fis.available();

            ba = new char[nb];
            isr.read(ba, 0, nb);
            fis.close();
            isr.close();
        } catch (Exception e) {
            return null;
        }
        return new String(ba);
    }

    public static void main(String[] args) {

        if (args.length < 2) {
            System.out.println("usage: java Reachability inputfilegraph inputfiletag");
            System.exit(-1);
        }

        String fileOfTags = loadFileData(new File(args[1]));
        if (fileOfTags == null) {
            System.out.println("Error when loading tags. Aborting...");
            System.exit(-1);
        }

        String[] tags = fileOfTags.split("\\R");

        System.out.println("Found " + tags.length + " tags");

        Graph g = new Graph();

        int b = g.makeFromFile(args[0]);

        if (b < 0) {
            System.out.println("Error when loading graph. Aborting...");
            System.exit(-1);
        }

        System.out.println("Graph loaded: " + g.getNbOfStates() + " states," + g.getNbOfTransitions()  + " transitions");




        try {
            String result = "";
            for(String tag: tags) {
                boolean isSatisfied = g.isReachabilitySatisfied(tag.trim());
                System.out.println("RESULT> Reachability of " + tag + ": " + isSatisfied);
                if (isSatisfied) {
                    result += "T";
                } else {
                    result += "F";
                }
            }
            System.out.println("FLAG: ph0wn{" + result + "}");
        } catch (Exception e) {
            System.out.println("Exception during graph analysis: " + e.getMessage() + "\n Aborting...");
            System.exit(-1);
        }

    }


}
