<?php

/**
 * Hoa
 *
 *
 * @license
 *
 * New BSD License
 *
 * Copyright © 2007-2012, Ivan Enderlin. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the Hoa nor the names of its contributors may be
 *       used to endorse or promote products derived from this software without
 *       specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDERS AND CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

namespace {

from('Hoa')

/**
 * \Hoa\Dns\Exception
 */
-> import('Dns.Exception');

}

namespace Hoa\Dns {

/**
 * Class \Hoa\Dns.
 *
 * Provide a tiny and very simple DNS server.
 * Please, see RFC6195, RFC1035 and RFC1034 for an overview.
 *
 * @author     Ivan Enderlin <ivan.enderlin@hoa-project.net>
 * @copyright  Copyright © 2007-2012 Ivan Enderlin.
 * @license    New BSD License
 */

class Dns implements \Hoa\Core\Event\Listenable {

    /**
     * Listeners.
     *
     * @var \Hoa\Core\Event\Listener object
     */
    protected $_on             = null;

    /**
     * Socket.
     *
     * @var \Hoa\Socket object
     */
    protected $_server         = null;

    /**
     * Type values for resources and queries.
     *
     * @var \Hoa\Dns\Light array
     */
    protected static $_types   = array(
        'invalid'     =>     0, // Invalid.
        // <RFC1035>
        'a'           =>     1, // Host address.
        'ns'          =>     2, // Authorative name server.
        'md'          =>     3, // Mail destination (obsolete, use MX).
        'mf'          =>     4, // Mail forwarder (obsolete, use MX).
        'cname'       =>     5, // Canonical name for an alias.
        'soa'         =>     6, // Start of a zone of authority.
        'mb'          =>     7, // Mailbox domain name.
        'mg'          =>     8, // Mail group member.
        'mr'          =>     9, // Mail rename name.
        'null'        =>    10, // Null resource record.
        'wks'         =>    11, // Well known service description.
        'ptr'         =>    12, // Domain name pointer.
        'hinfo'       =>    13, // Host information.
        'minfo'       =>    14, // Mailbox or mail list information.
        'mx'          =>    15, // Mail exchange.
        'txt'         =>    16, // Text strings.
        // </RFC1035>
        // <RFC1183>
        'rp'          =>    17, // Responsible person.
        'afsdb'       =>    18, // AFS cell database.
        'x25'         =>    19, // X_25 calling address.
        'isdn'        =>    20, // ISDN calling address.
        'rt'          =>    21, // Route through resource record.
        // </RFC1183>
        // <RFC1348>
        'nsap'        =>    22, // NSAP address.
        'ns_nsap_ptr' =>    23, // Reverse NSAP lookup (deprecated).
        // </RFC1348>
        // <RFC2065>
        'sig'         =>    24, // Security signature.
        'key'         =>    25, // Security key resource record.
        // </RFC2065>
        'px'          =>    26, // X.400 mail mapping.
        'gpos'        =>    27, // Geographical position (withdrawn).
        'aaaa'        =>    28, // IPv6 Address.
        'loc'         =>    29, // Location Information.
        // <RFC2065>
        'nxt'         =>    30, // Next domain.
        // </RFC2065>
        'eid'         =>    31, // Endpoint identifier.
        'nimloc'      =>    32, // Nimrod Locator.
        'srv'         =>    33, // Server Selection.
        'atma'        =>    34, // ATM Address.
        'naptr'       =>    35, // Naming Authority pointer.
        'kx'          =>    36, // Key Exchange.
        'cert'        =>    37, // Certification Record.
        'a6'          =>    38, // IPv6 Address (obsolete, use aaaa).
        'dname'       =>    39, // Non-terminal DNAME (for IPv6).
        'sink'        =>    40, // Kitchen sink.
        'opt'         =>    41, // EDNS0 option (meta-RR).
        // <RFC3123>
        'apl'         =>    42, // Address prefix list.
        // </RFC3123>
        'ds'          =>    43, // Delegation Signer
        'sshfp'       =>    44, // SSH Fingerprint
        'ipseckey'    =>    45, // IPSEC Key
        'rrsig'       =>    46, // RRSet Signature
        'nsec'        =>    47, // Negative Security
        'dnskey'      =>    48, // DNS Key
        'dhcid'       =>    49, // Dynamic host configuration identifier
        'nsec3'       =>    50, // Negative security type 3
        'nsec3param'  =>    51, // Negative security type 3 parameters
        'hip'         =>    55, // Host Identity Protocol
        'spf'         =>    99, // Sender Policy Framework
        'tkey'        =>   249, // Transaction key
        // <RFC2845>
        'tsig'        =>   250, // Transaction signature.
        // </RFC2845>
        'ixfr'        =>   251, // Incremental zone transfer.
        // <RFC5936>
        'axfr'        =>   252, // Transfer zone of authority.
        'mailb'       =>   253, // Transfer mailbox records.
        'maila'       =>   254, // Transfer mail agent records.
        // </RFC5936>
        'any'         =>   255, // Wildcard match.
        'zxfr'        =>   256, // BIND-specific, nonstandard.
        'dlv'         => 32769, // DNSSEC look-aside validation.
        'max'         => 65536
    );

    /**
     * Class values for resources and queries.
     *
     * @var \Hoa\Dns\Light array
     */
    protected static $_classes = array(
        'in'    =>   1, // Internet.
        'dc'    =>   2, // Data class.
        'ch'    =>   3, // Chaos.
        'hs'    =>   4, // Hesiod.
        'qnone' => 254, // QClass none.
        'qany'  => 255  // QClass any.
    );



    /**
     * Construct the DNS server.
     *
     * @access  public
     * @param   \Hoa\Socket\Server  $server    Server.
     * @return  void
     */
    public function __construct ( \Hoa\Socket\Server $server ) {

        if('udp' != $server->getSocket()->getTransport())
            throw new Exception(
                'Server must listen on UDP transport; given %s.',
                0, strtoupper($server->getSocket()->getTransport()));

        set_time_limit(0);

        $this->_server = $server;
        $this->_on     = new \Hoa\Core\Event\Listener($this, array('query'));

        return;
    }

    /**
     * Attach a callable to this listenable object.
     *
     * @access  public
     * @param   string  $listenerId    Listener ID.
     * @param   mixed   $callable      Callable.
     * @return  \Hoa\Dns
     * @throw   \Hoa\Core\Exception
     */
    public function on ( $listenerId, $callable ) {

        $this->_on->attach($listenerId, $callable);

        return $this;
    }

    /**
     * Run the server.
     *
     * @access  public
     * @return  void
     */
    public function run ( ) {

        $this->_server->considerRemoteAddress(true);
        $this->_server->connectAndWait();

        while(true) {

            $buffer = $this->_server->read(1024);

            if(empty($buffer))
                continue;

            $domain = null;
            $handle = substr($buffer, 12);

            for($i = 0, $m = strlen($handle); $i < $m; ++$i) {

                if(0 === $length = ord($handle[$i]))
                    break;

                if(null !== $domain)
                    $domain .= '.';

                $domain .= substr($handle, $i + 1, $length);
                $i      += $length;
            }

            $i    += 2;
            $type  = array_search(
                $_ = (int) (string) ord($handle[$i] + $handle[$i + 1]),
                static::$_types
            ) ?: $_;

            $i     += 2;
            $class  = array_search(
                $_  = (int) (string) ord($handle[$i]),
                static::$_classes
            ) ?: $_;

            $ips    = $this->_on->fire('query', new \Hoa\Core\Event\Bucket(array(
                'domain' => $domain,
                'type'   => $type,
                'class'  => $class
            )));
            $ip     = null;

            foreach(explode('.', $ips[0]) as $foo)
                $ip .= chr($foo);

            $this->_server->writeAll(
                $buffer[0] . $buffer[1] . chr(129)   . chr(128)   .
                $buffer[4] . $buffer[5] . $buffer[4] . $buffer[5] .
                chr(0)     . chr(0)     . chr(0)     . chr(0)     .
                $handle    . chr(192)   . chr(12)    . chr(0)     .
                chr(1)     . chr(0)     . chr(1)     . chr(0)     .
                chr(0)     . chr(0)     . chr(60)    . chr(0)     .
                chr(4)     . $ip
            );
        }

        $this->_server->disconnect();

        return;
    }
}

}
