all: polyglot.pdf

polyglot.pdf: analyses.pdf hidden.zip
	python3 createpolyglot.py --pdf analyses.pdf --zip hidden.zip
	zip -F analyses.pdf --out polyglot.pdf

hidden.zip:
	zip hidden.zip ./thezip/blah ./thezip/FLAG

clean:
	cp analyses.pdf.orig analyses.pdf
	rm -f polyglot.pdf hidden.zip

