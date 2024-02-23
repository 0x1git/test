#include <stdio.h>
#include <unistd.h>
#include <stdlib.h>

int main(int argc, char *argv[]) {
    int opt;
    char *filepath = NULL;
    int verbose = 0;
    char *output = NULL;

    // Parse command line options
    while ((opt = getopt(argc, argv, "l:o:v")) != -1) {
        switch (opt) {
            case 'l':
                filepath = optarg;
                break;
            case 'o':
                output = optarg;
                break;
            case 'v':
                verbose = 1;
                break;
            default: /* '?' */
                fprintf(stderr, "Usage: %s -l <file path> -o <output file> -v\n", argv[0]);
                exit(EXIT_FAILURE);
        }
    }

    // Check if required options are provided
    if (filepath == NULL) {
        fprintf(stderr, "Usage: %s -l <file path> -o <output file> -v\n", argv[0]);
        exit(EXIT_FAILURE);
    }

    // Print all the information
    printf("Filepath: %s\n", filepath);
    if (output != NULL) {
        printf("Output file: %s\n", output);
    }
    if (verbose) {
        printf("Verbose mode activated.\n");
    }

    return 0;
}
