#!/usr/bin/env python

#
# Copyright (c) 2009 Evert Pot
# All rights reserved.
# http://www.rooftopsolutions.nl/


import os
from optparse import OptionParser
import time 

def getfreespace(path):
	stat = os.statvfs(path)
	return stat.f_frsize * stat.f_bavail

def getbytesleft(path,treshold):
	return getfreespace(path)-treshold

def run(cacheDir, treshold, sleep=5, simulate=False):
	bytes = getbytesleft(cacheDir,treshold)
	if (bytes>0):
		print "Bytes to go before we hit treshhold:", bytes
	else:
		print "Treshold exceeded with:", -bytes, "bytes" 
		dir = os.listdir(cacheDir)
		dir2 = []
		for file in dir:
			path = cacheDir + '/' + file
			dir2.append({
				"path" : path,
				"atime": os.stat(path).st_atime,
				"size" : os.stat(path).st_size
			})

		dir2.sort(lambda x,y: int(x["atime"]-y["atime"]))
		
		filesunlinked = 0
		gainedspace = 0
		left = -bytes
		for file in dir2:
			if not simulate: os.unlink(file["path"])
			left = int(left - file["size"])
			gainedspace = gainedspace + file["size"]
			filesunlinked = filesunlinked + 1
			
			if(left<0):
				break

		print "%d files deleted (%d bytes)" % (filesunlinked, gainedspace)
		
	
	time.sleep(sleep)

	

def main():
	parser = OptionParser(
		version="naturalselecton v0.2",
		description="Cache directory manager. Deletes cache entries based on accesstime and free space tresholds",
		usage="usage: %prog [options] cacheDirectory"
	)
	parser.add_option(
		'-s',
		dest="simulate",
		action="store_true",
		help="Don't actually make changes, but just simulate the behaviour",
	)
	parser.add_option(
		'-r','--runs',
		help="How many times to check before exiting. -1 is infinite, which is the default",
		type="int",
		dest="runs",
		default=-1
	)
	parser.add_option(
		'-n','--interval',
		help="Sleep time in seconds (default = 5)",
		type="int",
		dest="sleep",
		default=5
	)
	parser.add_option(
		'-l','--treshold',
		help="Treshhold in bytes (default = 10737418240, which is 10GB)",
		type="int",
		dest="treshold",
		default=10737418240
	)


	options,args = parser.parse_args()
	if len(args)<1:
		parser.error("This utility requires at least 1 argument")	
	cacheDir = args[0]

	print "Natural Selection"
	print "Cache directory:", cacheDir
	free = getfreespace(cacheDir);
	print "Current free disk space:", free

	runs = options.runs;
	while runs!=0 :
		run(
			cacheDir,
			sleep=options.sleep,
			simulate=options.simulate,
			treshold=options.treshold
		)
		if runs>0:
			runs = runs - 1

if __name__ == '__main__' :
	main()
