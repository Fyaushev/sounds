#pragma once
#include "../server.h"
#include "DataSamples.cc"
#include "UniformDataSamples.cc"
#include "AudioFile.cc"
#include "bytevector.cc"
#include "../config.h"

#include <string>
#include <fcntl.h>
#include <unistd.h>
#include <iostream>
using namespace std;

static int readStringFromFile(int *data, int pos, int len, string &buf, int fileSize) { // len - length of a string to read
	int blocks = (len + 3) / 4; // == # of blocks to read, 1 block = 4 bytes (int)
	buf = "";
	if (fileSize < pos + blocks - 1) return 1; // file is too short
	int j = 0;
	while(j < blocks) {
		int a = data[pos+j];
		int b = a % (1 << 24); // first byte
		buf += (char) b;
		if ((j == blocks - 1) && (len % 4 == 1)) break; 
		b = (a >> 8) % (1 << 16); // second byte
		buf += (char) b;
		if ((j == blocks - 1) && (len % 4 == 2)) break; 
		b = (a >> 16) % (1 << 8); // third byte
		buf += (char) b;
		if ((j == blocks - 1) && (len % 4 == 3)) break; 
		b = a >> 24; // last byte
		buf += (char) b;
		j++;
	}
	return 0;
}

WAV_File::WAV_File() : AudioFile("", 0, -1, nullptr, 0, 0), Subchunk1Size(0), AudioFormat(0), NumChannels(0), ByteRate(0), BlockAlign(0), BitDepth(0) {}
// constructor for a prototype that is used by object factory
// file description as per http://soundfile.sapp.org/doc/WaveFormat/
	// if PCM then extra parameters do not exists and total data offset from the beginning of the file is 44 bytes (or 11 blocks)
	//The default byte ordering assumed for WAVE data files is little-endian. Files written using the big-endian byte ordering scheme have the identifier RIFX instead of RIFF.
	//The sample data must end on an even byte boundary. Whatever that means.
	//8-bit samples are stored as unsigned bytes, ranging from 0 to 255. 16-bit samples are stored as 2's-complement signed integers, ranging from -32768 to 32767.
	//There may be additional subchunks in a Wave data stream. If so, each will have a char[4] SubChunkID, and unsigned long SubChunkSize, and SubChunkSize amount of data.
	//RIFF stands for Resource Interchange File Format.

WAV_File::~WAV_File() {
	// since the file corresponding to an AudioFile is always opened while the audio is being used, we need to close fd upon destruction and unmap the memory
	// this destructor can also be called through any of the expections
	if (data == MAP_FAILED) { // in this case file was opened but mmap was failed
		close(fd);
	} else if ((data != nullptr) && (fd > 0)) { // in this case file was opened and mmap was successful
		munmap(data, size); // what to do if this fails?
		close(fd);
	} // else data = v.body and fd = -1 (as default)
}

int WAV_File::getObjId() const {
	return 0;
}

int WAV_File::init(bytevector const &v) {
	/*
	data - container with bytes of WAV file (and maybe code of a file?)
	
	1. Archives a bytevector as a .wav file with a particular (?) name
	2. Initializes all fields of the WAV_File object by parsing bytevector
		- "data" field is initialized as a mmap object (for now)
	
	return value:
		0: successful
		or exception is thrown
	*/
	
	// how to name the file? maybe file code should come in bytevector?
	string fcode = "file_code_123";  // this needs to change
	string fname = FILE_SAVE_DIRECTORY + "/" + fcode + ".wav";
	v.write_to_file(fname);
	
	file_id = fcode;
	size = v.size; // if v doesn't have extra bytes for code
	data = (int *) v.body; // temporarily
	
	string fileType, fileFormat;
	int err = readStringFromFile(data, 0, 4, fileType, size);
	if (err) throw "WAV_File::init(): parsing error: unable to read header";
	
	err = readStringFromFile(data, 2, 4, fileFormat, size);
	if (err) throw "WAV_File::init(): parsing error: unable to read header";
	
	if ((fileType != "RIFF") && (fileFormat != "WAVE")) {
		throw "WAV_File::init(): parsing error: format is not RIFF WAVE (bytes #0 and #2)";
	}
	
	if (size < 44) {
		throw "WAV_File::init(): parsing error: file is too short";
	}
	
	if (data[1]+8 != size) {
		throw "WAV_File::init(): parsing error: incorrect file size in WAV file in byte #1";
	}
	
	string buf;
	readStringFromFile(data, 3, 4, buf, size); // here size is guaranteed to be correct
	if (buf != "fmt ") throw "WAV_File::init(): parsing error: unable to read header at byte #3";
	
	this->Subchunk1Size = data[4];
	this->AudioFormat = data[5] % (1 << 16);
	if (AudioFormat != 1) {
		throw "WAV_File::init(): parsing error: audio format is not PCM (Pulse-code modulation) at byte #5";
	}
	this->NumChannels = data[5] >> 16;
	this->SampleRate = data[6];
	this->ByteRate = data[7];
	this->BlockAlign = data[8] % (1 << 16);
	this->BitDepth = data[8] >> 16;
	readStringFromFile(data, 9, 4, buf, size);
	if (buf != "data") throw "WAV_File::init(): parsing error: audio format is not PCM (Pulse-code modulation) at byte #9";
	this->NumSamples = 8 * data[10] / NumChannels / BitDepth;
	NumSamples = NumSamples / 2 * 2; // it has to be even because 1 sample = 16 bytes (half of integer)
	
	// all fields, except "fd" and "data" are filled. Now opening a file that was created earlier.
	//cout << fname << endl;
	fd = open(fname.c_str(), O_RDONLY);
	if (fd < 0) throw "WAV_File::init(): failed to open a file";
	
	data = (int *)mmap(0, size, PROT_READ, MAP_SHARED, fd, 0);
	if (data == MAP_FAILED) {
		close(fd);
		throw "WAV_File::init(): error mmaping the file";
	}

	return 0;
}

bytevector WAV_File::serialize() const {
	bytevector v;
	string path = FILE_SAVE_DIRECTORY + "/" + file_id + ".wav";
	v.read_from_file(path);
	return v;
}

WAV_File * WAV_File::clone() const {
	WAV_File *f;
	return f;
}

UniformDataSamples WAV_File::getSamples() const {
	/*
	Returns a container with all data points
	TODO: add stereo container	
	*/
	
	if (NumChannels != 1) throw "WAV_File::getSamples(): audio not in mono format";
	UniformDataSamples u(NumSamples);
	u.x_0 = 0;
	u.delta_x = 1 / ((double) SampleRate);	
	
	for (int i = 0; i < NumSamples / 2; i++) {
		u[2*i] = (double) (data[11+i] % (1 << 16));
		u[2*i+1] = (double) (data[11+i] >> 16);
	}
	
	return u;
}

void WAV_File::print() const {
	cout << "file_id: " << file_id << endl;
	cout << "size: " << size << " bytes" << endl;
	cout << "Subchunk1Size: " << Subchunk1Size << endl;
	cout << "AudioFormat: " << AudioFormat << endl;
	cout << "NumChannels: " << NumChannels << endl;
	cout << "SampleRate: " << SampleRate << endl;
	cout << "ByteRate: " << ByteRate << endl;
	cout << "BlockAlign: " << BlockAlign << endl;
	cout << "BitDepth: " << BitDepth << endl;
	cout << "NumSamples: " << NumSamples << endl;
}

