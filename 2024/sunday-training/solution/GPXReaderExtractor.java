import org.w3c.dom.*;
import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;
import java.io.File;

public class GPXReaderExtractor {
    public static void main(String[] args) {
        if (args.length < 1) {
            System.out.println("Usage: java GPXReaderExtractor <input_gpx_file>");
            return;
        }

        String inputFilePath = args[0];
        StringBuilder extractedString = new StringBuilder();

        try {
            // Load the GPX file
            File gpxFile = new File(inputFilePath);
            DocumentBuilderFactory dbFactory = DocumentBuilderFactory.newInstance();
            DocumentBuilder dBuilder = dbFactory.newDocumentBuilder();
            Document doc = dBuilder.parse(gpxFile);
            doc.getDocumentElement().normalize();

            // Get all track points
            NodeList trkptList = doc.getElementsByTagName("trkpt");

            for (int i = 0; i < trkptList.getLength(); i++) {
                Node trkpt = trkptList.item(i);

                if (trkpt.getNodeType() == Node.ELEMENT_NODE) {
                    Element trkptElement = (Element) trkpt;
                    NodeList extensionsList = trkptElement.getElementsByTagName("extensions");

                    if (extensionsList.getLength() > 0) {
                        Element extensionsElement = (Element) extensionsList.item(0);
                        NodeList hrList = extensionsElement.getElementsByTagName("gpxtpx:hr");

                        if (hrList.getLength() > 0) {
                            Element hrElement = (Element) hrList.item(0);
                            // Extract the value of "hr"
                            String hrValue = hrElement.getTextContent();
                            try {
                                int hrIntValue = Integer.parseInt(hrValue);
                                // Only add printable ASCII characters (values between 32 and 126)
                                if (hrIntValue >= 32 && hrIntValue <= 126) {
                                    extractedString.append((char) hrIntValue);
                                }
                            } catch (NumberFormatException e) {
                                // Ignore invalid integer values
                            }
                        }
                    }
                }
            }

            // Output the extracted string
            System.out.println("Extracted String: " + extractedString.toString());
        } catch (Exception e) {
            e.printStackTrace();
        }
    }
}
