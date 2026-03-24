package main

import (
	"encoding/binary"
	"flag"
	"fmt"
	"log"
	"math"
	"math/big"
	"os"
	"strings"
)

/* ----------------------- Config (defaults) ----------------------- */

const (
	baudRate    = 400    // bits per second (Manchester bit period)
	sampleRate  = 37500  // Hz
	carrierHz   = 1400   // I/Q carrier for data
	tone1Hz     = 1400   // optional preamble tone 1 (same as carrier by default)
	tone2Hz     = 1400   // optional preamble tone 2
	trailSilSec = 0.010  // 10 ms tail silence
)

// Message structure fields
const (
	preambleOnes = 15
	morseBits    = "000101111"
	natEmBits    = "1111"
)

/* ----------------------- CLI Flags ----------------------- */

var (
	outRaw    = flag.String("outraw", "transmission.txt", "path to write raw bitstream (0/1)")
	outWav    = flag.String("outwav", "biphaseL_iq_stereo.wav", "path to write stereo I/Q WAV")
	payload   = flag.String("payload", "MHV{EA}", "payload text for 61-bit BCH field (ASCII)")
	stdFlag   = flag.String("std", "ieee", "Manchester convention: ieee | thomas")
	invert    = flag.Bool("invert", false, "invert polarity (flip signs for all halves)")
	demoBit   = flag.Bool("demo", false, "include one demo bit before data")
	tone1Sec  = flag.Float64("tone1", 0.0, "preamble tone1 duration seconds (0=off)")
	tone2Sec  = flag.Float64("tone2", 0.0, "preamble tone2 duration seconds (0=off)")
	devHz     = flag.Float64("devhz", 0.0, "FSK-like deviation per half-bit (Hz), by Manchester level")
	kickRad   = flag.Float64("kick", 0.0, "phase kick (radians) at each half-bit boundary")
)

/* ----------------------- Utilities ----------------------- */

// textToBits returns "0101..." for 8-bit ASCII.
func textToBits(s string) string {
	var sb strings.Builder
	for i := 0; i < len(s); i++ {
		sb.WriteString(fmt.Sprintf("%08b", s[i]))
	}
	return sb.String()
}

// computeBCHParity: 21 bits for BCH(127,106) shortened to 82,61.
// g(x) = x^21 + x^19 + x^18 + x^17 + x^16 + x^14 + x^13 + x^11 + x^10 +
//        x^9 + x^7 + x^4 + x^3 + x + 1
func computeBCHParity(dataBits string) (string, error) {
	g := new(big.Int)
	for _, p := range []int{21, 19, 18, 17, 16, 14, 13, 11, 10, 9, 7, 4, 3, 1, 0} {
		g.SetBit(g, p, 1)
	}
	d := new(big.Int)
	if _, ok := d.SetString(dataBits, 2); !ok {
		return "", fmt.Errorf("invalid data bits")
	}
	d.Lsh(d, 21)
	for i := d.BitLen() - 1; i >= 21; i-- {
		if d.Bit(i) == 1 {
			shift := i - 21
			tmp := new(big.Int).Lsh(new(big.Int).Set(g), uint(shift))
			d.Xor(d, tmp)
		}
	}
	mask := new(big.Int).Sub(new(big.Int).Lsh(big.NewInt(1), 21), big.NewInt(1))
	parity := new(big.Int).And(d, mask)
	return fmt.Sprintf("%021b", parity), nil
}

// buildMessage pads payload to 61 bits and appends 21 parity bits -> 82 bits.
func buildMessage(payloadText string) (string, error) {
        // Protected data field (61 bits): 25-85
        // bit 25: format flag -> 0 = short message
	// bit 26: protocol flag -> 1
	// bits 27-36: country code --> 0111101100 = Monaco
	// bits 37-39: user protocol --> 100 test
	// bits 40-83: data --> flag
	// bits 84-85: homing signal --> 00
	// Error correcting code: 21 bits
	// bits 86-106: error correcting code BCH parity
	// page 5: the BCH algorithm uses a 61-bit data register and a 21-bit code word register

	payloadBits := textToBits(payloadText)
	if len(payloadBits) > 44 {
		return "", fmt.Errorf("payload too large for 44-bit field")
	}
	dataBits := "010111101100100" + payloadBits + "00"
	if len(dataBits) > 61 {
	   	// should not occur
		return "", fmt.Errorf("dataBits > 61 bits")
	}
	if len(dataBits) < 61 {
		dataBits += strings.Repeat("0", 61-len(dataBits))
	}
	parity, err := computeBCHParity(dataBits)
	if err != nil {
		return "", err
	}
	fmt.Printf("payload=%s (len=%d) parity=%s (len=%d)\n",textToBits(payloadText), len(textToBits(payloadText)), parity, len(parity))
	return dataBits + parity, nil
}

// toneIQ generates I/Q with continuous starting phase; returns final phase.
func toneIQ(freq float64, durSec float64, fs int, startPhase float64) ([]float32, []float32, float64) {
	n := int(math.Round(durSec * float64(fs)))
	if n <= 0 {
		return []float32{}, []float32{}, startPhase
	}
	i := make([]float32, n)
	q := make([]float32, n)
	dphi := 2 * math.Pi * freq / float64(fs)
	phi := startPhase
	for k := 0; k < n; k++ {
		i[k] = float32(math.Cos(phi))
		q[k] = float32(math.Sin(phi))
		phi += dphi
		// wrap a bit to keep phi bounded (optional)
		if phi > 4*math.Pi {
			phi -= 2 * math.Pi
		}
	}
	return i, q, phi
}

// writeWAVStereo16 writes interleaved int16 PCM stereo.
func writeWAVStereo16(path string, fs int, left, right []float32) error {
	if len(left) != len(right) {
		return fmt.Errorf("left/right channel length mismatch")
	}
	numSamples := len(left)

	// Convert to int16 (interleaved)
	data := make([]int16, 2*numSamples)
	for i := 0; i < numSamples; i++ {
		l := left[i]
		if l < -1 {
			l = -1
		} else if l > 1 {
			l = 1
		}
		r := right[i]
		if r < -1 {
			r = -1
		} else if r > 1 {
			r = 1
		}
		data[2*i] = int16(math.Round(float64(l) * 32767))
		data[2*i+1] = int16(math.Round(float64(r) * 32767))
	}

	// WAV header
	byteRate := fs * 2 * 2                     // sampleRate * channels * bytesPerSample
	blockAlign := uint16(2 * 2)                // channels * bytesPerSample
	subchunk2Size := uint32(len(data) * 2)     // int16 -> 2 bytes
	chunkSize := 36 + subchunk2Size

	f, err := os.Create(path)
	if err != nil {
		return err
	}
	defer f.Close()

	// RIFF
	if _, err := f.Write([]byte("RIFF")); err != nil {
		return err
	}
	if err := binary.Write(f, binary.LittleEndian, uint32(chunkSize)); err != nil {
		return err
	}
	if _, err := f.Write([]byte("WAVE")); err != nil {
		return err
	}

	// fmt
	if _, err := f.Write([]byte("fmt ")); err != nil {
		return err
	}
	if err := binary.Write(f, binary.LittleEndian, uint32(16)); err != nil { // PCM
		return err
	}
	if err := binary.Write(f, binary.LittleEndian, uint16(1)); err != nil { // PCM format
		return err
	}
	if err := binary.Write(f, binary.LittleEndian, uint16(2)); err != nil { // stereo
		return err
	}
	if err := binary.Write(f, binary.LittleEndian, uint32(fs)); err != nil {
		return err
	}
	if err := binary.Write(f, binary.LittleEndian, uint32(byteRate)); err != nil {
		return err
	}
	if err := binary.Write(f, binary.LittleEndian, blockAlign); err != nil {
		return err
	}
	if err := binary.Write(f, binary.LittleEndian, uint16(16)); err != nil { // bits/sample
		return err
	}

	// data
	if _, err := f.Write([]byte("data")); err != nil {
		return err
	}
	if err := binary.Write(f, binary.LittleEndian, subchunk2Size); err != nil {
		return err
	}
	for _, v := range data {
		if err := binary.Write(f, binary.LittleEndian, v); err != nil {
			return err
		}
	}
	return nil
}

/* ----------------------- Core: Manchester with fractional timing, optional FSK dev + phase kick ----------------------- */

// buildBiphaseIQ renders tones (optional) + demo bit (optional) + Manchester data with
// exact half-bit timing (no drift) and continuous carrier phase.
// std: "ieee" => 1: -1 then +1, 0: +1 then -1
//      "thomas" => 1: +1 then -1, 0: -1 then +1
// invert flips all signs.
// devHz: add +/- deviation (Hz) per half-bit based on its sign (+1 => +dev, -1 => -dev).
// kick: add a phase kick (radians) at *each* half-bit boundary.
func buildBiphaseIQ(bits string, std string, invert bool, includeDemo bool, tone1Dur, tone2Dur, devHz, kick float64) ([]float32, []float32) {
	// Select sign mapping
	var first1, second1, first0, second0 float32
	switch strings.ToLower(std) {
	case "thomas":
		first1, second1 = +1, -1
		first0, second0 = -1, +1
	default: // "ieee"
		first1, second1 = -1, +1
		first0, second0 = +1, -1
	}
	if invert {
		first1, second1 = -first1, -second1
		first0, second0 = -first0, -second0
	}

	fs := float64(sampleRate)
	baseDphi := 2 * math.Pi * float64(carrierHz) / fs
	phi := 0.0

	iAll := make([]float32, 0, 1<<16)
	qAll := make([]float32, 0, 1<<16)

	// Tone 1 (continuous phase)
	if tone1Dur > 0 {
		i1, q1, phi1 := toneIQ(tone1Hz, tone1Dur, sampleRate, phi)
		iAll = append(iAll, i1...)
		qAll = append(qAll, q1...)
		phi = phi1
	}

	// Optional demo bit (render exactly one full bit using fractional timing)
	if includeDemo {
		Thalf := 1.0 / (2.0 * float64(baudRate))
		nDemo := int(math.Round(2.0 * Thalf * fs))
		b1f, b1s := first1, second1 // signs for a logical '1'
		boundary := int(math.Round(Thalf * fs))
		for n := 0; n < nDemo; n++ {
			sign := b1f
			if n >= boundary {
				// kick once at mid-bit boundary
				if n == boundary && kick != 0 {
					phi += kick
					if phi > 2*math.Pi {
						phi -= 2 * math.Pi
					} else if phi < 0 {
						phi += 2 * math.Pi
					}
				}
				sign = b1s
			}
			// FSK-like deviation by sign
			thisDphi := baseDphi + 2*math.Pi*(func() float64 {
				if sign < 0 {
					return -devHz
				}
				return devHz
			}())/fs

			iAll = append(iAll, sign*float32(math.Cos(phi)))
			qAll = append(qAll, sign*float32(math.Sin(phi)))
			phi += thisDphi
			if phi > 4*math.Pi {
				phi -= 2 * math.Pi
			} else if phi < -2*math.Pi {
				phi += 2 * math.Pi
			}
		}
	}

	// Tone 2 (continuous phase)
	if tone2Dur > 0 {
		i2, q2, phi2 := toneIQ(tone2Hz, tone2Dur, sampleRate, phi)
		iAll = append(iAll, i2...)
		qAll = append(qAll, q2...)
		phi = phi2
	}

	// Manchester data with fractional half-bit timing
	Thalf := 1.0 / (2.0 * float64(baudRate))
	totalHalves := len(bits) * 2
	totalDur := float64(totalHalves) * Thalf
	nData := int(math.Round(totalDur * fs))

	for n := 0; n < nData; n++ {
		t := float64(n) / fs
		h := int(math.Floor(t / Thalf))
		if h >= totalHalves {
			h = totalHalves - 1
		}
		bitIdx := h >> 1
		half := h & 1

		var f, s float32
		if bits[bitIdx] == '1' {
			f, s = first1, second1
		} else {
			f, s = first0, second0
		}

		// detect half boundary (n-1 -> n crosses)
		if n > 0 {
			tPrev := float64(n-1) / fs
			hPrev := int(math.Floor(tPrev / Thalf))
			if hPrev != h && kick != 0 {
				phi += kick
				if phi > 2*math.Pi {
					phi -= 2 * math.Pi
				} else if phi < 0 {
					phi += 2 * math.Pi
				}
			}
		}

		sign := f
		if half == 1 {
			sign = s
		}

		// FSK-like deviation by sign of the half
		thisDphi := baseDphi + 2*math.Pi*(func() float64 {
			if sign < 0 {
				return -devHz
			}
			return devHz
		}())/fs

		iAll = append(iAll, sign*float32(math.Cos(phi)))
		qAll = append(qAll, sign*float32(math.Sin(phi)))
		phi += thisDphi
		if phi > 4*math.Pi {
			phi -= 2 * math.Pi
		} else if phi < -2*math.Pi {
			phi += 2 * math.Pi
		}
	}

	// Trailing silence
	nSil := int(math.Round(trailSilSec * fs))
	if nSil > 0 {
		iAll = append(iAll, make([]float32, nSil)...)
		qAll = append(qAll, make([]float32, nSil)...)
	}

	// Normalize
	var peak float32
	for _, v := range iAll {
		if a := float32(math.Abs(float64(v))); a > peak {
			peak = a
		}
	}
	for _, v := range qAll {
		if a := float32(math.Abs(float64(v))); a > peak {
			peak = a
		}
	}
	if peak < 1e-12 {
		peak = 1.0
	}
	inv := 1 / peak
	for k := range iAll {
		iAll[k] *= inv
	}
	for k := range qAll {
		qAll[k] *= inv
	}
	return iAll, qAll
}

/* ----------------------- main ----------------------- */

func main() {
	flag.Parse()

	// Build 82-bit BCH field and assemble full raw bitstream
	msgField, err := buildMessage(*payload)
	if err != nil {
		log.Fatalf("buildMessage: %v", err)
	}
	preamble := strings.Repeat("1", preambleOnes)
	fullStream := preamble + morseBits + msgField + natEmBits
	fmt.Printf("full=%s\n\tpreamble=%s\n\tmorseBits=%s\n\tmsg=%s nat=%s\n", fullStream, preamble, morseBits, msgField, natEmBits)

	// Write raw bitstream
	if err := os.WriteFile(*outRaw, []byte(fullStream), 0644); err != nil {
		log.Fatalf("write %s: %v", *outRaw, err)
	}
	fmt.Printf("Wrote %d raw bits to %s\n", len(fullStream), *outRaw)

	// Render I/Q WAV with fractional Manchester + optional FSK deviation & phase kick
	i, q := buildBiphaseIQ(
		fullStream,
		*stdFlag,
		*invert,
		*demoBit,
		*tone1Sec,
		*tone2Sec,
		*devHz,
		*kickRad,
	)
	if err := writeWAVStereo16(*outWav, sampleRate, i, q); err != nil {
		log.Fatalf("writeWAVStereo16: %v", err)
	}
	fmt.Printf("Wrote stereo I/Q WAV (%d samples) to %s\n", len(i), *outWav)
}

