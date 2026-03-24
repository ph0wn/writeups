# picobank

As explained in the description of the challenge, `picobank` is a bank account manager.
The goal was to create an account with administrator permissions and access the flag.

## Short description

Interactions with `picobank` are done through a serial port.
It takes the form of a command handler and lets the user manipulate a bank account.

The `picobank` firmware embeds:
- A custom heap, checking for heap invalid modifications.
- A PRNG system based on a hardware PRNG.
- A command system.

Here is a list of commands the user can try:
- `a`: Buy an admin token against 99999$.
- `b`: Show the bank's vault.
- `c`: Create a new bank account. One bank account max can be opened.
- `d`: Delete an existing bank account.
- `l`: Lock the bank account's safe. 
- `m`: Ask money to PicoLeCroco.
- `k`: Show the secret key stored in the user's vault, if it is open.
- `r`: Reset the user's password. 
- `s`: Show the user's vault.
- `u`: Unlock the user's vault (the password must be correct).
- `z`: Try to unlock the bank's vault (the user's token must match the admin token).

Any other command will trigger an error.

## The vulnerability

The commands are not directly vulnerable (or at least are not supposed to be vulnerable).
The intended way to exploit the firmware was to exploit the way errors get handled.
When an error is triggered:
- The firmware shows an error message
- It then calls recursively the main command loop

This is where the first vulnerability lies: after enough calls, the stack (which grows downward) will meet the heap, allowing for stack / heap confusion.
The problem is that the heap is hardened: each heap chunk embeds a secret tag. The main program regularly checks these tags, and crashes if one of them is invalid.
Thus, any brutal modification to a heap chunk's header will lead to a hard error and would require the reset the board.

```
heap start                                                                                                                                                            heap end
    |                                                                                                                                                                     |
    | hdr0 | <--------- alloc ---------> | hdr1 | <--- free ---> | hdr2 | <----- alloc -----> | hdr3 | <----------------------------- free -----------------------------> |
    |                                    |                       |                            |                                                                           |
     ============= chunk 0 =============  ======= chunk 1 ======  ========= chunk 2 =========  ================================== chunk 3 ================================
```

Notice that free chunk's headers also have a secret hash getting checked.
Since it is not easily possible to modify an allocated chunk without hitting a free chunk's header, the intended solution was to find another way to somehow allocate the user account's struct at the very end of the heap.
To that end, it was possible to exploit a second vulnerability: if an error occurs while the secret key gets allocated (for example, if the safe is closed or the user account does not exist), it is never freed.

The intended solution was to first allocate a secret key before opening an account big enough to fill the heap, such that there is only enough space for the new account.
Then, create a new account, which will be located at the end of the heap, with no free block afterwards.

```
heap start                                                                                                                                                            heap end
    |                                                                                                                                                                     |
    | hdr0 | <---------------------------------------------------- unfreed secret key -----------------------------------------------------> | hdr1 | <---- account ----> |
```

Now, there is no header between the account's struct and the growing stack.
We only need to overflow the stack just enough to reach the account, without touching `hdr1` to avoid triggering the heap overflow.

## Exploitation

The full exploit can be found in `solve.py`.

Basically, the idea was to first allocate the empty secret key with a correct size (as explained above), create an account (the exact name / password does not matter), and then enter enough wrong commands to reach the account's struct in the heap.
Finally, trigger a password reset (the password will be written on the stack while parsing the command) and fill it with the administrator's cookie (in our case, it neeeded to be shifted by 2 bytes to align correctly in memory with the struct's field).
The last step is to trigger the `z` + `b` commands to unlock the admin's vault and get the flag.
