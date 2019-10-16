<?php


namespace sunframework\system;


class GitHelper {
    /** @var $repositoryPath StringUtil */
    private $repositoryPath;

    /**
     * Setup a repository at the absolute path. If the value is null, will use the root of the project.
     * GitHelper constructor.
     * @param StringUtil $repositoryPath where the .git folder is located
     */
    public function __construct(StringUtil $repositoryPath = null) {
        if (!$repositoryPath) {
            $repositoryPath = dirname($_SERVER["DOCUMENT_ROOT"]);
        }
        $this->repositoryPath = $repositoryPath;
    }

    /**
     * Before using the git helper, it usually good practice to check if the git executable
     * is installed.
     * @return bool true if git is installed on the system.
     */
    public static function installed() {
        return self::commandExists('git');
    }

    /**
     * Check if the repository exists.
     * @return bool true if the repository exists.
     */
    public function exists() {
        $cmd = "cd $this->repositoryPath && git rev-parse --is-inside-work-tree";
        exec($cmd, $output);
        return trim($output[0]) == 'true';
    }

    /**
     * Returns all the available tags that match the semver
     *      /^v(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(-[a-zA-Z\d][-a-zA-Z.\d]*)?(\+[a-zA-Z\d][-a-zA-Z.\d]*)?$/
     * @return array<StringUtil>
     */
    public function listTags() {
        $pattern = '/^v(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(-[a-zA-Z\d][-a-zA-Z.\d]*)?(\+[a-zA-Z\d][-a-zA-Z.\d]*)?$/';
        $cmd = "cd $this->repositoryPath && git tag -l v*"; // cannot do a perfect match, but at least only take those that starts with v.
        exec($cmd, $output);
        return array_filter($output, function($tag) use($pattern) {
            return preg_match($pattern, $tag);
        });
    }

    /**
     * @param StringUtil $tagName
     * @return bool true if succeed, false otherwise.
     */
    public function pullVersion(StringUtil $tagName) {
        $cmd = "cd $this->repositoryPath && git checkout tags/$tagName";
        exec($cmd, $output, $returnCode);
        return intval($returnCode) == 0;
    }

    private static function isWindows() {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    private static function commandExists($command) {
        $test = self::isWindows() ? "where" : "which";
        return is_executable(trim(shell_exec("$test $command")));
    }
}