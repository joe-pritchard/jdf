# JDF #

A Simple package for creating JDF files and sending them via JMF.

[JDF](https://en.wikipedia.org/wiki/Job_Definition_Format) is an XML standard used to send files to digital printers and
communicate workflow steps to printers and finishers. JMF is a communications protocol used to query "JMF Managers" 
(RIP computers and print servers etc) for job/queue status, and to submit JDF files to them for processing

This package does not implement the entirety of either of these standards, it simple enables you to create JDF files
for the printing of an arbitrary number of files, and then send those files to a JMF manager, and query the Manager for 
the status of its jobs.

## Installation ##

`composer require joe-pritchard/jdf`

## Usage ##
 