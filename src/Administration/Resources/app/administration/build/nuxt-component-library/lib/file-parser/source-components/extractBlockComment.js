const docblockParser = require('docblock-parser');

module.exports = (ast) => {
    if (!ast.comments || !ast.comments[0]) {
        return {};
    }

    let componentDeclaration = null;

    ast.body.forEach((entry) => {
        if (entry.type !== 'ExpressionStatement') {
            return;
        }

        const calleeObject = entry.expression.callee.object;

        // when Component.register
        if (calleeObject.name === 'Component') {
            componentDeclaration = entry;
            return;
        }

        // when Cicada.Component.register
        if (calleeObject.object
            && calleeObject.object.name === 'Cicada'
            && calleeObject.property
            && calleeObject.property.name === 'Component'
        ) {
            componentDeclaration = entry;
        }
    });

    if (!componentDeclaration) {
        return {};
    }

    let commentForComponentDeclaration;

    const componentDeclarationStartLine = componentDeclaration.loc.start.line;
    ast.comments.forEach((comment) => {
        // stop when first block comment was found
        if (commentForComponentDeclaration) {
            return;
        }

        // just parse block comments
        if (comment.type !== 'Block') {
            return;
        }

        const commentEndLine = comment.loc.end.line;

        // to make sure that also one line comment is allowed
        if (commentEndLine + 2 >= componentDeclarationStartLine) {
            commentForComponentDeclaration = comment.value;
        }
    });

    commentForComponentDeclaration = `/**\n${commentForComponentDeclaration}\n*/`;

    const result = docblockParser({
        tags: {
            public: docblockParser.booleanTag,
            private: docblockParser.booleanTag,
            description: docblockParser.multilineTilTag,
            'example-type': docblockParser.singleParameterTag,
            'component-example': docblockParser.multilineTilTag,
            status: docblockParser.singleParameterTag,
            deprecated: docblockParser.singleParameterTag
        }
    }).parse(commentForComponentDeclaration);


    return {
        public: result.tags.public,
        private: result.tags.private,
        example: result.tags['component-example'] || '',
        exampleType: result.tags['example-type'] || 'none',
        status: result.tags.status || 'n/a',
        description: result.tags.description || '',
        deprecated: ((result.tags || {}).deprecated || '').replace('tag:v', '') || ''
    };
};
