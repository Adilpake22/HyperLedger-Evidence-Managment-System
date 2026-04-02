<?php
class Block {
    public $index;
    public $timestamp;
    public $data;
    public $previousHash;
    public $hash;

    public function __construct($index, $data, $previousHash = '0000000000000000000000000000000000000000000000000000000000000000') {
        $this->index        = $index;
        $this->timestamp    = time();
        $this->data         = $data;
        $this->previousHash = $previousHash;
        $this->hash         = $this->calculateHash();
    }

    public function calculateHash() {
        $content =
            $this->index .
            $this->timestamp .
            $this->previousHash .
            json_encode($this->data);
        return hash('sha256', $content);
    }
}

class Blockchain {
    public $chain = [];

    public function __construct() {
        $this->chain[] = $this->createGenesisBlock();
    }

    private function createGenesisBlock() {
        return new Block(0, 'Genesis Block', '0000000000000000000000000000000000000000000000000000000000000000');
    }

    public function getLatestBlock() {
        return $this->chain[count($this->chain) - 1];
    }

    public function addBlock($data) {
        $prevBlock     = $this->getLatestBlock();
        $newBlock      = new Block(
            count($this->chain),
            $data,
            $prevBlock->hash
        );
        $this->chain[] = $newBlock;
        return $newBlock;
    }

    public function isChainValid() {
        for ($i = 1; $i < count($this->chain); $i++) {
            $current  = $this->chain[$i];
            $previous = $this->chain[$i - 1];
            if ($current->hash !== $current->calculateHash()) return false;
            if ($current->previousHash !== $previous->hash)   return false;
        }
        return true;
    }
}

class EvidenceBlockchain extends Blockchain {

    public function addEvidence($caseId, $file, $submittedBy, $description = "") {
        return $this->addBlock([
            'type'        => 'EVIDENCE_SUBMIT',
            'caseId'      => $caseId,
            'file'        => $file,
            'submittedBy' => $submittedBy,
            'description' => $description,
            'timestamp'   => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Called whenever an existing evidence record is modified.
     * Creates a NEW block linked to the previous hash — original block is never touched.
     *
     * @param string $evidenceId   The EvidenceID being modified (e.g. EVID-2026-003)
     * @param string $caseId
     * @param string $modifiedBy   Officer who made the change
     * @param string $reason       Mandatory reason for modification
     * @param array  $changedFields Key→value of what changed: ['EvidenceStatus'=>'Verified', ...]
     * @param string $previousHash The BlockchainHash of the LAST block for this evidence
     */
    public function addModification($evidenceId, $caseId, $modifiedBy, $reason, $changedFields, $previousHash) {
        // Build the new block using the previous evidence hash as the chain link
        $prevBlock = $this->getLatestBlock();
        $newBlock  = new Block(
            count($this->chain),
            [
                'type'          => 'EVIDENCE_MODIFY',
                'evidenceId'    => $evidenceId,
                'caseId'        => $caseId,
                'modifiedBy'    => $modifiedBy,
                'reason'        => $reason,
                'changedFields' => $changedFields,
                'timestamp'     => date('Y-m-d H:i:s')
            ],
            $previousHash   // Link to the hash of the block being modified
        );
        $this->chain[] = $newBlock;
        return $newBlock;
    }

    public function addCase($caseId, $caseName, $createdBy) {
        return $this->addBlock([
            'type'      => 'CASE',
            'caseId'    => $caseId,
            'caseName'  => $caseName,
            'createdBy' => $createdBy,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    public function addTransfer($caseId, $evidenceId, $fromUser, $toUser, $reason = "") {
        return $this->addBlock([
            'type'       => 'TRANSFER',
            'caseId'     => $caseId,
            'evidenceId' => $evidenceId,
            'fromUser'   => $fromUser,
            'toUser'     => $toUser,
            'reason'     => $reason,
            'timestamp'  => date('Y-m-d H:i:s')
        ]);
    }
   
}
?>
