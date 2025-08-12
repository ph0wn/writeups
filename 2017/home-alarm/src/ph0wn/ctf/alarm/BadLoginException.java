package ph0wn.ctf.alarm;

public class BadLoginException extends Exception {
    public BadLoginException(String msg) {
	super(msg);
    }

    /* unused - will be removed from release code,
       but is useful in debug to generate the right bytecode
       this function merely does an XOR with 0x21 on a string and reveals a passphrase
    void passXor() {
	// oNcTSFM@SR`UiNLD = NoBurglarsAtHome ^ 0x21
	byte [] s = new byte [] { 'o', 'N', 'c', 'T', 'S', 'F', 'M', '@', 'S', 'R', '`', 'U', 'i', 'N', 'L', 'D' };
	for (int i=0;i< s.length; i++) {
	    s[i] = (byte) (s[i] ^ 0x21);
	}
	} */

    /* unused  decoy switch - we'll hide passXor within that */
    int sherlock(int v) {
	int hints;
	switch(v) {
	case 0:
	    hints = 1;
	    break;
	case 1:
	    hints = 2;
	    break;
	default:
	    hints = 3;
	}

	return hints;
    }
}
