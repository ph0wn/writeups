local cmds = require('commands')
local getopt = require('getopt')
local utils =  require('utils')

author = '@cryptax'
version = 'v0.2'
desc = [[
This script writes data to a glucose sensor
 ]]
example = [[

	 script run axelle

]]
usage = [[
script run axelle -h 

Arguments:
	-h             : this help
]]

local DEBUG = true
--- 
-- A debug printout-function
local function dbg(args)
    if not DEBUG then return end
    if type(args) == "table" then
		local i = 1
		while args[i] do
			dbg(args[i])
			i = i+1
		end
	else
		print("###", args)
	end	
end	
--- 
-- This is only meant to be used when errors occur
local function oops(err)
	print("ERROR: ",err)
	return nil, err
end
--- 
-- Usage help
local function help()
	print(author)	
	print(version)	
	print(desc)
	print('Example usage')
	print(example)
end
--
--- Flash tag with Flag
local function flashtag()
	print('Unlock tag')
 	core.console("hf 15 cmd raw -c 02A407C2AD7521")
	print('Write flag')
	core.console("hf 15 cmd write u 0x30 4d65646963616c20")
	core.console("hf 15 cmd write u 0x31 4c61623a20687474")
	core.console("hf 15 cmd write u 0x32 703a2f2f31302e32")
	core.console("hf 15 cmd write u 0x33 31302e31372e3636")
	core.console("hf 15 cmd write u 0x34 3a32313334352069")
	core.console("hf 15 cmd write u 0x35 643a7069636f2070")
	core.console("hf 15 cmd write u 0x36 77643a3139393930")
	core.console("hf 15 cmd write u 0x37 343031200a000000")
	print('Lock tag')
	core.console("hf 15 cmd raw -c 02A207C2AD7521")
end
--

--- 
-- The main entry point
function main(args)

	print( string.rep('--',20) )
	print( string.rep('--',20) )	
	print()

	flashtag()	
end

main(args)
