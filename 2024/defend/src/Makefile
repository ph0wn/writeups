%.pdf: %.md
	@pandoc $< -o $@ --from markdown -V colorlinks=true

clean:
	rm -f specs.pdf
